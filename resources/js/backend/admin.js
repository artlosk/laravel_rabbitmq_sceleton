import '../../js/bootstrap';
import 'bootstrap';
import 'admin-lte';
import select2 from 'select2';
import 'select2/dist/css/select2.min.css';

select2($);

import './darkMode';
import { initDateInputs } from './dateInput';
import './users';
import './post-notification-settings';
import './roles-manage';

initDateInputs();

if (typeof window.updateDarkModeButtonState === 'function') {
    if (document.readyState === 'complete') {
        window.updateDarkModeButtonState();
    } else {
        window.addEventListener('load', function () {
            window.updateDarkModeButtonState();
        });
    }
}
