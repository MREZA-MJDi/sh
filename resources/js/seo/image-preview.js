document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-seo-image-preview]').forEach((root) => {
        const form = root.closest('form');
        const fileInput = form?.querySelector('[data-image-file]');
        const positionInput = form?.querySelector('[data-image-position]');
        const image = root.querySelector('[data-preview-image]');
        const articleImage = root.querySelector('[data-preview-article-image]');
        const hero = root.querySelector('[data-preview-hero]');
        const articleBanner = root.querySelector('[data-preview-article-banner]');
        const placement = form?.querySelector('[data-image-placement]');
        const titleFields = form?.querySelectorAll('[data-image-title]');
        const excerptField = form?.querySelector('[data-image-excerpt]');
        const dimensionHint = root.querySelector('[data-preview-dimensions]');
        let objectUrl = null;

        const checkDimensions = (node) => {
            if (!node?.naturalWidth || !node?.naturalHeight || !dimensionHint) return;
            const width = node.naturalWidth;
            const height = node.naturalHeight;
            const ratio = width / height;
            const isArticle = root.dataset.previewKind === 'article';
            const isHero = root.dataset.previewKind === 'banner' && (!placement || placement.value === 'home_hero');
            let warning = false;
            let message = 'ابعاد فایل: ' + width + '×' + height + ' پیکسل';
            if (isArticle && (width < 900 || Math.abs(ratio - (16 / 9)) > 0.18)) {
                warning = true;
                message += ' · پیشنهاد: حداقل ۹۰۰×۵۰۶ با نسبت ۱۶:۹ برای وضوح و برش بهتر کارت';
            } else if (isHero && (width < 1600 || ratio < 1.5)) {
                warning = true;
                message += ' · پیشنهاد: تصویر افقی حداقل ۱۶۰۰ پیکسل عرض برای نمایش بهتر هیرو';
            } else if (!isArticle && !isHero && width < 1200) {
                warning = true;
                message += ' · پیشنهاد: عرض ۱۲۰۰ پیکسل یا بیشتر برای وضوح بهتر بنر';
            } else {
                message += ' · اندازه برای جایگاه انتخاب‌شده مناسب است';
            }
            dimensionHint.textContent = message;
            dimensionHint.classList.toggle('is-warning', warning);
        };

        const syncTitle = () => {
            const value = [...(titleFields || [])].find((field) => field.value.trim())?.value.trim() || 'عنوان تصویر';
            root.querySelectorAll('[data-preview-title], [data-preview-article-title]').forEach((node) => { node.textContent = value; });
        };
        const syncExcerpt = () => {
            const target = root.querySelector('[data-preview-excerpt]');
            if (target && excerptField) target.textContent = excerptField.value.trim() || 'خلاصه مقاله در این بخش نمایش داده می‌شود.';
        };
        const setPosition = (value) => {
            if (positionInput) positionInput.value = value;
            [image, articleImage].forEach((node) => { if (node) node.style.objectPosition = value; });
        };
        const syncPlacement = () => {
            const isHero = !placement || placement.value === 'home_hero';
            if (hero) hero.hidden = !isHero;
            if (articleBanner) articleBanner.hidden = isHero;
            const hint = root.querySelector('[data-preview-dimensions]');
            if (hint && root.dataset.previewKind === 'banner') {
                hint.textContent = isHero
                    ? 'صفحه اصلی: تصویر پس‌زمینه تمام‌عرض، ارتفاع هم‌راستا با ارتفاع hero و لایه‌ی متنی روی تصویر'
                    : 'صفحه مقالات: تصویر تمام‌عرض با حفظ نسبت اصلی فایل؛ در خروجی عمومی بدون کشیدگی نمایش داده می‌شود';
            }
        };
        const setImage = (src) => {
            [image, articleImage].forEach((node) => {
                if (!node) return;
                node.onload = () => checkDimensions(node);
                node.src = src;
                node.hidden = false;
            });
            root.querySelector('[data-preview-placeholder]')?.setAttribute('hidden', '');
        };
        fileInput?.addEventListener('change', () => {
            const file = fileInput.files?.[0];
            if (!file) return;
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = URL.createObjectURL(file);
            setImage(objectUrl);
        });
        placement?.addEventListener('change', () => { syncPlacement(); checkDimensions(placement.value === 'home_hero' ? image : (articleImage || image)); });
        [image, articleImage].forEach((node) => node?.addEventListener('load', () => checkDimensions(node)));
        titleFields?.forEach((field) => field.addEventListener('input', syncTitle));
        excerptField?.addEventListener('input', syncExcerpt);
        const stages = [hero, articleBanner, root.querySelector('.seo-admin-blog-image')].filter(Boolean);
        stages.forEach((stage) => {
            let dragging = false;
            const updateFromPointer = (event) => {
                const rect = stage.getBoundingClientRect();
                if (!rect.width || !rect.height) return;
                const x = Math.max(0, Math.min(100, Math.round(((event.clientX - rect.left) / rect.width) * 100)));
                const y = Math.max(0, Math.min(100, Math.round(((event.clientY - rect.top) / rect.height) * 100)));
                setPosition(x + '% ' + y + '%');
            };
            stage.addEventListener('pointerdown', (event) => {
                if (stage.hidden || (!image?.src && !articleImage?.src)) return;
                dragging = true;
                stage.setPointerCapture?.(event.pointerId);
                updateFromPointer(event);
            });
            stage.addEventListener('pointermove', (event) => { if (dragging) updateFromPointer(event); });
            stage.addEventListener('pointerup', () => { dragging = false; });
            stage.addEventListener('pointercancel', () => { dragging = false; });
        });
        setPosition(positionInput?.value || '50% 50%');
        syncTitle();
        syncExcerpt();
        syncPlacement();
        checkDimensions(placement?.value === 'home_hero' ? image : (articleImage || image));
    });
});
