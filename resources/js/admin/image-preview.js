const imageInputs = 'input[type="file"][accept*="image"]';

const createPreview = (input) => {
    const field = input.closest('.adm-field') || input.parentElement;
    if (!field) return null;

    let stage = field.querySelector(':scope > .adm-image-preview');
    if (stage) return stage;

    stage = document.createElement('div');
    stage.className = 'adm-image-preview';
    stage.hidden = true;

    const image = document.createElement('img');
    image.alt = 'پیش‌نمایش تصویر انتخاب‌شده';
    image.loading = 'eager';

    const meta = document.createElement('div');
    meta.className = 'adm-image-preview-meta';
    meta.setAttribute('aria-live', 'polite');
    meta.hidden = true;

    stage.append(image);
    stage.addEventListener('click', (event) => event.preventDefault());
    field.append(stage, meta);

    return stage;
};

const showPreview = (input) => {
    const file = input.files?.[0];
    const stage = createPreview(input);
    if (!stage) return;

    const previousUrl = stage.dataset.objectUrl;
    if (previousUrl) URL.revokeObjectURL(previousUrl);

    if (!file) {
        stage.hidden = true;
        stage.parentElement.querySelector(':scope > .adm-image-preview-meta')?.setAttribute('hidden', '');
        stage.querySelector('img')?.removeAttribute('src');
        stage.dataset.objectUrl = '';
        return;
    }

    if (!file.type.startsWith('image/')) {
        stage.hidden = true;
        return;
    }

    const image = stage.querySelector('img');
    const meta = stage.parentElement.querySelector(':scope > .adm-image-preview-meta');
    meta.hidden = false;
    const objectUrl = URL.createObjectURL(file);
    stage.dataset.objectUrl = objectUrl;
    stage.style.aspectRatio = input.dataset.previewRatio || '16 / 9';
    stage.dataset.fit = input.dataset.previewFit || 'contain';
    stage.hidden = false;

    image.onload = () => {
        const width = image.naturalWidth;
        const height = image.naturalHeight;
        const ratio = (width / height).toFixed(2);
        meta.replaceChildren();

        const dimensions = document.createElement('span');
        dimensions.textContent = `ابعاد اصلی: ${width} × ${height} پیکسل`;

        const ratioLabel = document.createElement('strong');
        ratioLabel.textContent = `نسبت تصویر: ${ratio}:1`;

        const size = document.createElement('span');
        size.textContent = `حجم فایل: ${(file.size / 1048576).toFixed(2)} مگابایت`;

        meta.append(dimensions, ratioLabel, size);
    };

    image.onerror = () => {
        meta.textContent = 'پیش‌نمایش این تصویر ممکن نیست؛ فرمت فایل را بررسی کنید.';
    };

    image.src = objectUrl;
};

document.addEventListener('change', (event) => {
    const input = event.target;
    if (input instanceof HTMLInputElement && input.matches(imageInputs)) {
        showPreview(input);
    }
});

document.addEventListener('reset', (event) => {
    queueMicrotask(() => {
        event.target.querySelectorAll?.(imageInputs).forEach((input) => showPreview(input));
    });
}, true);

document.addEventListener('close', (event) => {
    if (!(event.target instanceof HTMLDialogElement)) return;
    event.target.querySelectorAll('.adm-image-preview[data-object-url]').forEach((stage) => {
        if (stage.dataset.objectUrl) URL.revokeObjectURL(stage.dataset.objectUrl);
        stage.dataset.objectUrl = '';
        stage.hidden = true;
    });
}, true);
