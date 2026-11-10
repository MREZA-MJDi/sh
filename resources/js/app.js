import './bootstrap';
import './admin/panel.js';

document.addEventListener('click', (event) => {
    if (!(event.target instanceof Element)) {
        return;
    }

    const accountButton = event.target.closest('[data-demo-account]');

    if (!(accountButton instanceof HTMLButtonElement)) {
        return;
    }

    const emailInput = document.getElementById('email');
    const passwordInput = document.getElementById('password');

    if (!(emailInput instanceof HTMLInputElement) || !(passwordInput instanceof HTMLInputElement)) {
        return;
    }

    emailInput.value = accountButton.dataset.demoEmail ?? '';
    passwordInput.value = accountButton.dataset.demoPassword ?? '';
    passwordInput.focus();
});
