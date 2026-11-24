(() => {
    const root = document.getElementById('admin-inbox');
    if (!root) return;

    root.querySelector('#ib-back')?.addEventListener('click', () => root.classList.remove('chat'));
    if (window.matchMedia('(max-width: 760px)').matches && new URLSearchParams(window.location.search).has('message')) {
        root.classList.add('chat');
    }

    root.querySelector('[data-inbox-edit]')?.addEventListener('click', () => {
        document.getElementById('ib-edit-dialog')?.showModal();
    });
    document.querySelector('[data-inbox-broadcast]')?.addEventListener('click', () => {
        document.getElementById('ib-broadcast-dialog')?.showModal();
    });
    document.querySelectorAll('[data-inbox-close]').forEach(button => {
        button.addEventListener('click', () => button.closest('dialog')?.close());
    });

    const password = document.getElementById('ib-new-password');
    document.getElementById('ib-toggle-password')?.addEventListener('click', event => {
        if (!password) return;
        password.type = password.type === 'password' ? 'text' : 'password';
        event.currentTarget.textContent = password.type === 'password' ? 'نمایش' : 'پنهان';
    });
    document.getElementById('ib-generate-password')?.addEventListener('click', () => {
        if (!password) return;
        const chars = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789!@#$';
        const values = new Uint32Array(16);
        window.crypto.getRandomValues(values);
        password.value = [...values].map(value => chars[value % chars.length]).join('');
        password.type = 'text';
    });

    const file = document.getElementById('ib-avatar-file');
    file?.addEventListener('change', () => {
        const selected = file.files?.[0];
        if (!selected) return;
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(selected.type) || selected.size > 2 * 1024 * 1024) {
            file.value = '';
            window.alert('تصویر باید JPG، PNG یا WebP و حداکثر ۲ مگابایت باشد.');
            return;
        }
        const preview = document.getElementById('ib-edit-preview');
        const url = URL.createObjectURL(selected);
        preview.replaceChildren();
        const image = document.createElement('img');
        image.src = url;
        image.alt = '';
        image.onload = () => URL.revokeObjectURL(url);
        preview.append(image);
    });

    root.querySelector('[data-mark-read]')?.requestSubmit();

    const messages = document.getElementById('ib-msgs');
    if (messages) messages.scrollTop = messages.scrollHeight;
})();
