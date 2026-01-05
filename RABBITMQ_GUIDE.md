# RabbitMQ Guide

## Быстрый старт

### 1. Установка пакета

```bash
./vendor/bin/sail composer require vladimir-yuldashev/laravel-queue-rabbitmq
```

### 2. Настройка .env

Добавьте в `.env`:

```env
QUEUE_CONNECTION=rabbitmq

RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/
RABBITMQ_QUEUE=default

# Порты для Docker (опционально)
FORWARD_RABBITMQ_PORT=5672
FORWARD_RABBITMQ_DASHBOARD_PORT=15672
```

### 3. Запуск воркера

```bash
./vendor/bin/sail artisan queue:work rabbitmq
```

---

## Management UI

**URL**: http://localhost:15672

**Учетные данные**:
- Логин: `guest`
- Пароль: `guest`

**Возможности**:
- Просмотр очередей и сообщений
- Мониторинг производительности
- Управление пользователями и правами
- Просмотр подключений и каналов

---

## Основные команды

### Работа с очередями

```bash
# Запустить воркер
./vendor/bin/sail artisan queue:work rabbitmq

# Запустить воркер для конкретной очереди
./vendor/bin/sail artisan queue:work rabbitmq --queue=high-priority

# Запустить воркер с ограничением времени
./vendor/bin/sail artisan queue:work rabbitmq --timeout=60

# Запустить воркер с ограничением попыток
./vendor/bin/sail artisan queue:work rabbitmq --tries=3

# Обработать только одно задание
./vendor/bin/sail artisan queue:work rabbitmq --once

# Остановить воркеры после текущего задания
./vendor/bin/sail artisan queue:restart
```

### Управление заданиями

```bash
# Список неудачных заданий
./vendor/bin/sail artisan queue:failed

# Повторить неудачное задание
./vendor/bin/sail artisan queue:retry {id}

# Повторить все неудачные задания
./vendor/bin/sail artisan queue:retry all

# Удалить неудачное задание
./vendor/bin/sail artisan queue:forget {id}

# Очистить все неудачные задания
./vendor/bin/sail artisan queue:flush

# Очистить очередь
./vendor/bin/sail artisan queue:clear rabbitmq
```

### Просмотр логов

```bash
# Laravel Pail
./vendor/bin/sail artisan pail

# Логи контейнера
./vendor/bin/sail logs -f

# Логи RabbitMQ
docker logs -f rabbit-rabbitmq
```

---

## Создание заданий (Jobs)

### Создать Job

```bash
./vendor/bin/sail artisan make:job ProcessOrder
```

### Пример Job

```php
<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $orderId;

    public function __construct(int $orderId)
    {
        $this->orderId = $orderId;
    }

    public function handle(): void
    {
        Log::info("Processing order: {$this->orderId}");
        
        // Ваша логика обработки заказа
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("Failed to process order {$this->orderId}: {$exception->getMessage()}");
    }
}
```

### Отправка заданий

```php
use App\Jobs\ProcessOrder;

// Простая отправка
ProcessOrder::dispatch($orderId);

// С задержкой
ProcessOrder::dispatch($orderId)->delay(now()->addMinutes(10));

// В конкретную очередь
ProcessOrder::dispatch($orderId)->onQueue('orders');

// С приоритетом
ProcessOrder::dispatch($orderId)->onQueue('high-priority');
```

---

## Работа с очередями

### Приоритеты очередей

```php
// Высокий приоритет
MyJob::dispatch()->onQueue('high-priority');

// Обычный приоритет
MyJob::dispatch()->onQueue('default');

// Низкий приоритет
MyJob::dispatch()->onQueue('low-priority');
```

### Запуск воркеров с приоритетами

```bash
# Обрабатывает high-priority первым, затем default, затем low-priority
./vendor/bin/sail artisan queue:work rabbitmq --queue=high-priority,default,low-priority
```

### Просмотр сохранённых писем (для разработки)

```bash
# Список всех писем
./vendor/bin/sail exec laravel.test ls -lah storage/app/private/emails/

# Просмотр конкретного письма
./vendor/bin/sail exec laravel.test cat storage/app/private/emails/имя_файла.html

# Найти все письма
./vendor/bin/sail exec laravel.test find storage/app/private/emails -name "*.html" -type f

# Удалить все письма
./vendor/bin/sail exec laravel.test rm -rf storage/app/private/emails/*.html
```

### Batch заданий

```php
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

$batch = Bus::batch([
    new ProcessOrder(1),
    new ProcessOrder(2),
    new ProcessOrder(3),
])
->then(function (Batch $batch) {
    // Все задания выполнены
})
->catch(function (Batch $batch, \Throwable $e) {
    // Первое задание провалилось
})
->finally(function (Batch $batch) {
    // Batch завершен
})
->dispatch();
```

### Цепочка заданий

```php
use Illuminate\Support\Facades\Bus;

Bus::chain([
    new DownloadFile($url),
    new ProcessFile($path),
    new UploadFile($path),
])->dispatch();
```

---

## Docker команды

### Управление контейнером

```bash
# Проверить статус
docker ps | grep rabbit-rabbitmq

# Перезапустить
./vendor/bin/sail restart rabbitmq

# Просмотр логов
docker logs -f rabbit-rabbitmq

# Войти в контейнер
docker exec -it rabbit-rabbitmq sh
```

### RabbitMQ CLI

```bash
# Статус RabbitMQ
docker exec -it rabbit-rabbitmq rabbitmqctl status

# Список очередей
docker exec -it rabbit-rabbitmq rabbitmqctl list_queues

# Список подключений
docker exec -it rabbit-rabbitmq rabbitmqctl list_connections

# Список каналов
docker exec -it rabbit-rabbitmq rabbitmqctl list_channels

# Список пользователей
docker exec -it rabbit-rabbitmq rabbitmqctl list_users
```

### Управление пользователями

```bash
# Создать пользователя
docker exec -it rabbit-rabbitmq rabbitmqctl add_user username password

# Дать права
docker exec -it rabbit-rabbitmq rabbitmqctl set_permissions -p / username ".*" ".*" ".*"

# Сделать администратором
docker exec -it rabbit-rabbitmq rabbitmqctl set_user_tags username administrator

# Удалить пользователя
docker exec -it rabbit-rabbitmq rabbitmqctl delete_user username

```

---

## Мониторинг

### Через Management UI

1. Откройте http://localhost:15672
2. Вкладка **Queues** - просмотр очередей
3. Вкладка **Connections** - активные подключения
4. Вкладка **Channels** - открытые каналы
5. Вкладка **Admin** - управление пользователями

### Через CLI

```bash
# Статистика очередей
docker exec -it rabbit-rabbitmq rabbitmqctl list_queues name messages consumers

# Статистика подключений
docker exec -it rabbit-rabbitmq rabbitmqctl list_connections

# Использование памяти
docker exec -it rabbit-rabbitmq rabbitmqctl status | grep memory
```

---

## Производительность

1. **Запустите несколько воркеров**:
```bash
# В разных терминалах или через Supervisor
./vendor/bin/sail artisan queue:work rabbitmq &
./vendor/bin/sail artisan queue:work rabbitmq &
./vendor/bin/sail artisan queue:work rabbitmq &
```

2. **Используйте Supervisor** для автозапуска воркеров:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/html/artisan queue:work rabbitmq --sleep=3 --tries=3
autostart=true
autorestart=true
user=sail
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/html/storage/logs/worker.log
```

---

## Система уведомлений о постах

### Настройка получателей

1. Перейдите в админ-панель: **Администрирование → Уведомления о постах**
2. Создайте настройку:
   - **Тип уведомления**: Роль или Пользователь
   - **Получатели**: Выберите одну или несколько ролей/пользователей
   - **Активность**: Включите/выключите уведомления

**Важно:** Может существовать только одна настройка для ролей и одна для пользователей.

### Работа с письмами (Development)

```bash
# Убедитесь, что в .env установлено:
MAIL_MAILER=file

# Список всех писем
./vendor/bin/sail exec laravel.test ls -lah storage/app/private/emails/

# Просмотр письма
./vendor/bin/sail exec laravel.test cat storage/app/private/emails/2026-01-05_07-00-38_695b6196a1722.html

# Найти последнее письмо
./vendor/bin/sail exec laravel.test ls -t storage/app/private/emails/*.html | head -1

# Просмотр последнего письма
./vendor/bin/sail exec laravel.test cat $(ls -t storage/app/private/emails/*.html | head -1)

# Удалить все письма
./vendor/bin/sail exec laravel.test rm -rf storage/app/private/emails/*.html

# Количество писем
./vendor/bin/sail exec laravel.test ls -1 storage/app/private/emails/*.html | wc -l
```

### Проверка работы системы

1. **Запустите воркер**:
```bash
./vendor/bin/sail artisan queue:work rabbitmq --queue=notifications --tries=3 --timeout=60
```

2. **Создайте пост** через админ-панель

3. **Проверьте логи**:
```bash
./vendor/bin/sail artisan pail
# или
./vendor/bin/sail exec laravel.test tail -f storage/logs/laravel.log | grep "Post notification"
```

4. **Проверьте RabbitMQ UI**: http://localhost:15672
   - Вкладка **Queues** → очередь `notifications`
   - Должны видеть обработанные сообщения

5. **Проверьте письма**:
```bash
./vendor/bin/sail exec laravel.test ls -lah storage/app/private/emails/
```

### Переключение на реальную отправку (Production)

1. Настройте SMTP в `.env`:
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"
```

2. Перезапустите контейнеры:
```bash
./vendor/bin/sail restart
```

3. Перезапустите воркер:
```bash
./vendor/bin/sail artisan queue:restart
./vendor/bin/sail artisan queue:work rabbitmq --queue=notifications --tries=3 --timeout=60
```
