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
  if (!response.ok) {
    const validationMessage = Object.values(result.errors || {}).flat()[0];
    const error = new Error(validationMessage || result.message || (response.status === 413 ? 'وب‌سرور یا PHP درخواست را بزرگ‌تر از حد مجاز تشخیص داد؛ client_max_body_size و post_max_size را بررسی کنید.' : 'ارتباط با سرور ناموفق بود.'));
    error.status = response.status;
    throw error;
  }
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
  previewMeta.className = 'adm-muted teacher-help';
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

    const courseSelect = widget.querySelector('[data-upload-course]');
    const courseId = Number(courseSelect?.value || widget.dataset.courseId || 0);
    if (!courseId) {
      setStatus('ابتدا دوره موردنظر را انتخاب کنید.', true);
      courseSelect?.focus();
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

    const resumeKey = ['sh-video-upload', target, courseId, file.name, file.size, file.lastModified].join(':');
    startButton.disabled = true;
    fileInput.disabled = true;
    if (courseSelect) courseSelect.disabled = true;
    let started = null;
    let completedUpload = false;
    const receivedChunks = new Set();
    progress.hidden = false;
    setProgress(0);
    setStatus('در حال آماده‌سازی آپلود امن…');

    try {
      const savedUploadId = widget.dataset.resumeUrl ? localStorage.getItem(resumeKey) : null;
      if (savedUploadId) {
        try {
          started = await jsonRequest(widget.dataset.resumeUrl, {upload_id: savedUploadId});
          (started.received_chunks || []).forEach((index) => receivedChunks.add(Number(index)));
          setStatus('نشست قبلی پیدا شد؛ ادامه آپلود از بخش‌های باقی‌مانده…');
        } catch (resumeError) {
          if (![404, 409, 410].includes(resumeError.status)) throw resumeError;
          localStorage.removeItem(resumeKey);
          started = null;
        }
      }

      if (!started) {
        const startPayload = {
          target,
          target_id: courseId,
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
        if (widget.dataset.resumeUrl) localStorage.setItem(resumeKey, started.upload_id);
      }

      const chunkSize = Number(started.chunk_size_bytes || 0);
      const total = Number(started.total_chunks || 0);
      if (!chunkSize || !total || total !== Math.ceil(file.size / chunkSize)) {
        throw new Error('اطلاعات نشست آپلود با فایل انتخاب‌شده مطابقت ندارد؛ بارگذاری را دوباره آغاز کنید.');
      }

      setProgress((receivedChunks.size / total) * 92);
      for (let index = 0; index < total; index++) {
        if (receivedChunks.has(index)) continue;

        const form = new FormData();
        form.append('upload_id', started.upload_id);
        form.append('index', String(index));
        form.append('chunk', file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)), file.name);

        let chunkUploaded = false;
        let chunkError = null;
        for (let attempt = 1; attempt <= 4 && !chunkUploaded; attempt++) {
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
            if (!response.ok) {
              const validationMessage = Object.values(result.errors || {}).flat()[0];
              const error = new Error(validationMessage || result.message || (response.status === 413 ? 'وب‌سرور درخواست را بزرگ‌تر از حد مجاز تشخیص داد؛ محدودیت client_max_body_size هاست را بررسی کنید.' : 'ارسال بخشی از فایل ناموفق بود.'));
              error.status = response.status;
              throw error;
            }
            chunkUploaded = true;
            receivedChunks.add(index);
          } catch (error) {
            chunkError = error;
            const retryable = !error.status || error.status >= 500 || error.status === 408 || error.status === 429;
            if (attempt < 4 && retryable) await sleep(700 * attempt);
            else break;
          }
        }
        if (!chunkUploaded) throw chunkError || new Error('ارسال فایل ناموفق بود.');
        setProgress((receivedChunks.size / total) * 92);
        setStatus('در حال ارسال فایل… بخش ' + receivedChunks.size + ' از ' + total);
      }

      const completed = await jsonRequest(widget.dataset.completeUrl, {
        upload_id: started.upload_id,
        total,
        filename: file.name,
      });
      completedUpload = true;
      setProgress(100);

      if (target === 'teaser') {
        if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
        setStatus('فایل دریافت شد؛ در حال پردازش تریلر…');
        const statusUrl = widget.dataset.statusUrl;
        if (statusUrl) {
          for (let attempt = 0; attempt < 90; attempt++) {
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
          setStatus('پردازش تریلر هنوز ادامه دارد؛ وضعیت را کمی بعد دوباره بررسی کنید.');
        } else {
          setStatus('تریلر بارگذاری شد و در صف پردازش قرار گرفت.');
        }
      } else {
        if (widget.dataset.statusUrl) {
          setStatus('آپلود کامل شد؛ فایل در فضای خصوصی در حال پردازش و آماده‌سازی پخش است…');
          for (let attempt = 0; attempt < 90; attempt++) {
            await sleep(2000);
            const state = await jsonRequest(widget.dataset.statusUrl, {upload_id: started.upload_id});
            if (state.status === 'ready') {
              if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
              setStatus('ویدیو پردازش شد و آماده پخش است. صفحه در حال به‌روزرسانی است.');
              window.location.reload();
              return;
            }
            if (state.status === 'waiting_contract') {
              if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
              setStatus('فایل با موفقیت پردازش و در فضای خصوصی ذخیره شد؛ اتصال SpotPlayer برای شناسه خارجی در انتظار قرارداد است.');
              window.setTimeout(() => window.location.reload(), 1600);
              return;
            }
            if (state.status === 'failed') {
              if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
              throw new Error(state.error || 'پردازش ویدیو ناموفق بود. فایل را دوباره بارگذاری کنید.');
            }
          }
          if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
          setStatus('پردازش ویدیو هنوز ادامه دارد؛ وضعیت آن در فهرست ویدیوها به‌روزرسانی خواهد شد.');
          startButton.disabled = false;
          fileInput.disabled = false;
          if (courseSelect) courseSelect.disabled = false;
        } else {
          if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
          setStatus(completed.status === 'ready'
            ? 'ویدیو با موفقیت در فضای خصوصی ذخیره شد و آماده پخش است.'
            : 'ویدیو ثبت شد؛ وضعیت پردازش یا اتصال آن در فهرست ویدیوها نمایش داده می‌شود.');
          window.setTimeout(() => window.location.reload(), 1600);
        }
      }
    } catch (error) {
      if (completedUpload && widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
      const resumable = started?.upload_id && !completedUpload;
      const suffix = resumable
        ? ' نشست آپلود حفظ شد؛ با انتخاب همین فایل و زدن دوباره دکمه، از بخش‌های دریافت‌شده ادامه می‌دهید.'
        : '';
      setStatus((error.message || 'بارگذاری فایل ناموفق بود.') + suffix, true);
      startButton.disabled = false;
      fileInput.disabled = false;
      if (courseSelect) courseSelect.disabled = false;
    }
  });
});
