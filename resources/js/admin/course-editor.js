const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const jsonRequest = async (url, payload) => {
  const response = await fetch(url, {
    method: 'POST',
    credentials: 'same-origin',
    headers: {
      'Accept': 'application/json',
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': csrf(),
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
  });
  let result = {};
  try { result = await response.json(); } catch (_) {}
  if (!response.ok) throw new Error(result.message || 'ارتباط با سرور ناموفق بود.');
  return result;
};

const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));

document.querySelectorAll('[data-course-upload]').forEach((widget) => {
  const fileInput = widget.querySelector('[data-upload-file]');
  const startButton = widget.querySelector('[data-upload-start]');
  const status = widget.querySelector('[data-upload-status]');
  const progress = widget.querySelector('[data-upload-progress]');
  const bar = widget.querySelector('[data-upload-bar]');
  if (!fileInput || !startButton || !status || !progress || !bar) return;

  const preview = document.createElement('div');
  preview.hidden = true;
  preview.className = 'adm-video-upload-preview';
  preview.style.cssText = 'margin-top:12px;max-width:100%;';
  const previewVideo = document.createElement('video');
  previewVideo.controls = true;
  previewVideo.preload = 'metadata';
  previewVideo.playsInline = true;
  previewVideo.style.cssText = 'display:block;width:100%;max-height:320px;border-radius:12px;background:#0f172a;';
  const previewMeta = document.createElement('p');
  previewMeta.className = 'adm-muted';
  previewMeta.style.marginTop = '8px';
  preview.append(previewVideo, previewMeta);
  fileInput.insertAdjacentElement('afterend', preview);
  let previewUrl = null;

  fileInput.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    previewVideo.removeAttribute('src');
    previewVideo.load();
    const selected = fileInput.files?.[0];
    if (!selected) {
      preview.hidden = true;
      return;
    }
    previewUrl = URL.createObjectURL(selected);
    previewVideo.src = previewUrl;
    previewMeta.textContent = selected.name + ' · ' + (selected.size / 1048576).toFixed(1) + ' مگابایت';
    preview.hidden = false;
  });
  window.addEventListener('beforeunload', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
  });

  const setStatus = (message, isError = false) => {
    status.textContent = message;
    status.dataset.state = isError ? 'error' : 'info';
  };
  const setProgress = (percent) => {
    progress.hidden = false;
    bar.style.width = Math.max(0, Math.min(100, percent)) + '%';
  };

  startButton.addEventListener('click', async () => {
    const file = fileInput.files?.[0];
    if (!file) {
      setStatus('ابتدا فایل ویدیویی را انتخاب کنید.', true);
      fileInput.focus();
      return;
    }

    const maxMb = Number(widget.dataset.maxMb || 0);
    if (maxMb > 0 && file.size > maxMb * 1024 * 1024) {
      setStatus('حجم فایل از سقف ' + maxMb + ' مگابایت بیشتر است.', true);
      return;
    }

    const target = widget.dataset.target;
    const titleInput = widget.querySelector('[data-upload-title]');
    const title = titleInput?.value?.trim() || file.name.replace(/\.[^.]+$/, '');
    if (target === 'spotplayer_video' && !title) {
      setStatus('عنوان جلسه ویدیو را وارد کنید.', true);
      titleInput?.focus();
      return;
    }

    startButton.disabled = true;
    fileInput.disabled = true;
    let started = null;
    let completedUpload = false;
    progress.hidden = false;
    setProgress(0);
    setStatus('در حال آماده‌سازی آپلود امن…');

    try {
      const chunkSizeFallback = Number(widget.dataset.chunkMb || 5) * 1024 * 1024;
      const startPayload = {
        target,
        target_id: Number(widget.dataset.courseId),
        total: Math.ceil(file.size / chunkSizeFallback),
        total_bytes: file.size,
      };
      if (target === 'spotplayer_video') {
        startPayload.title = title;
        const classroomId = widget.querySelector('[data-upload-classroom]')?.value;
        const externalId = widget.querySelector('[data-upload-external]')?.value?.trim();
        if (classroomId) startPayload.classroom_id = Number(classroomId);
        if (externalId) startPayload.external_id = externalId;
      }

      started = await jsonRequest(widget.dataset.startUrl, startPayload);
      const chunkSize = Number(started.chunk_size_bytes || chunkSizeFallback);
      const total = Math.ceil(file.size / chunkSize);
      if (total !== startPayload.total) throw new Error('تنظیم اندازه قطعه آپلود با سرور هماهنگ نیست.');

      for (let index = 0; index < total; index++) {
        const form = new FormData();
        form.append('upload_id', started.upload_id);
        form.append('index', String(index));
        form.append('chunk', file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)), file.name);

        let chunkUploaded = false;
        let chunkError = null;
        for (let attempt = 1; attempt <= 3 && !chunkUploaded; attempt++) {
          try {
            const response = await fetch(widget.dataset.chunkUrl, {
              method: 'POST',
              credentials: 'same-origin',
              headers: {
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf(),
                'X-Requested-With': 'XMLHttpRequest',
              },
              body: form,
            });
            let result = {};
            try { result = await response.json(); } catch (_) {}
            if (!response.ok) throw new Error(result.message || 'ارسال بخشی از فایل ناموفق بود.');
            chunkUploaded = true;
          } catch (error) {
            chunkError = error;
            if (attempt < 3) await sleep(700 * attempt);
          }
        }
        if (!chunkUploaded) throw chunkError || new Error('ارسال فایل ناموفق بود.');
        setProgress(((index + 1) / total) * 92);
        setStatus('در حال ارسال فایل… بخش ' + (index + 1) + ' از ' + total);
      }

      const completed = await jsonRequest(widget.dataset.completeUrl, {
        upload_id: started.upload_id,
        total,
        filename: file.name,
      });
      completedUpload = true;
      setProgress(100);

      if (target === 'teaser') {
        setStatus('فایل دریافت شد؛ در حال پردازش تریلر…');
        const statusUrl = widget.dataset.statusUrl;
        if (statusUrl) {
          for (let attempt = 0; attempt < 30; attempt++) {
            await sleep(2000);
            const response = await fetch(statusUrl, {
              credentials: 'same-origin',
              headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
            });
            if (!response.ok) break;
            const state = await response.json();
            if (state.status === 'ready') {
              setStatus('تریلر آماده نمایش است. صفحه در حال به‌روزرسانی است.');
              window.location.reload();
              return;
            }
            if (state.status === 'failed') {
              throw new Error(state.error || 'پردازش تریلر ناموفق بود.');
            }
          }
          setStatus('پردازش تریلر هنوز ادامه دارد؛ چند لحظه دیگر صفحه را تازه کنید.');
          startButton.disabled = false;
          fileInput.disabled = false;
        } else {
          setStatus('تریلر بارگذاری شد و در صف پردازش قرار گرفت.');
        }
      } else {
        setStatus('ویدیو با موفقیت دریافت و در کتابخانه خصوصی ثبت شد. وضعیت اتصال ارائه‌دهنده: در انتظار قرارداد.');
        window.setTimeout(() => window.location.reload(), 1600);
      }
    } catch (error) {
      if (started?.upload_id && !completedUpload && widget.dataset.cancelUrl) {
        try { await jsonRequest(widget.dataset.cancelUrl, {upload_id: started.upload_id}); } catch (_) {}
      }
      setStatus(error.message || 'بارگذاری فایل ناموفق بود.', true);
      startButton.disabled = false;
      fileInput.disabled = false;
    }
  });
});
