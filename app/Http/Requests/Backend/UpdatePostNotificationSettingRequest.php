<?php

namespace App\Http\Requests\Backend;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePostNotificationSettingRequest extends FormRequest
{
    public function authorize()
    {
        return $this->user()->hasPermissionTo('access-admin-panel');
    }

    public function rules()
    {
        return [
            'notify_type' => 'required|in:role,user',
            'role_names' => 'required_if:notify_type,role|nullable|array',
            'role_names.*' => 'string|exists:roles,name',
            'user_ids' => 'required_if:notify_type,user|nullable|array',
            'user_ids.*' => 'exists:users,id',
            'is_active' => 'nullable|boolean',
        ];
    }

    public function messages()
    {
        return [
            'notify_type.required' => 'Выберите тип уведомления',
            'notify_type.in' => 'Тип уведомления должен быть "роль" или "пользователь"',
            'role_names.required_if' => 'Выберите хотя бы одну роль',
            'role_names.array' => 'Роли должны быть массивом',
            'role_names.*.exists' => 'Выбранная роль не существует',
            'user_ids.required_if' => 'Выберите хотя бы одного пользователя',
            'user_ids.array' => 'Пользователи должны быть массивом',
            'user_ids.*.exists' => 'Выбранный пользователь не существует',
            'is_active.boolean' => 'Поле "Активность" должно быть булевым значением',
        ];
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $postNotificationSetting = $this->route('post_notification_setting');
        
        throw (new \Illuminate\Validation\ValidationException($validator))
            ->errorBag($this->errorBag)
            ->redirectTo(route('backend.post-notification-settings.edit', $postNotificationSetting));
    }
}

