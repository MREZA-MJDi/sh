const dialog = document.getElementById('panel-role-switcher');

const openButton = document.querySelector('[data-role-switcher-open]');
const closeButtons = document.querySelectorAll('[data-role-switcher-close]');

const closeRoleDialog = () => {
    if (dialog instanceof HTMLDialogElement && dialog.open) dialog.close();
};

openButton?.addEventListener('click', () => {
    if (!(dialog instanceof HTMLDialogElement)) return;
    if (!dialog.open) dialog.showModal();
});

closeButtons.forEach((button) => button.addEventListener('click', closeRoleDialog));

dialog?.addEventListener('click', (event) => {
    if (event.target === dialog) closeRoleDialog();
});

dialog?.querySelectorAll('.panel-role-card.is-available').forEach((card) => {
    card.addEventListener('click', closeRoleDialog);
});
