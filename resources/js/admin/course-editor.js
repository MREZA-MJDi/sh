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
    progress.hidden = false;
    setProgress(0);
    setStatus('در حال آماده‌سازی آپلود امن…');

    try {
      const chunkSizeFallback = 4 * 1024 * 1024;
      const startPayload = {
        target,
        target_id: Number(widget.dataset.courseId),
        total: Math.ceil(file.size / chunkSizeFallback),
      };
      if (target === 'spotplayer_video') {
        startPayload.title = title;
        const classroomId = widget.querySelector('[data-upload-classroom]')?.value;
        const externalId = widget.querySelector('[data-upload-external]')?.value?.trim();
        if (classroomId) startPayload.classroom_id = Number(classroomId);
        if (externalId) startPayload.external_id = externalId;
      }

      // The server returns its configured chunk size. Start once with the
      // provisional count, then restart with the exact count if necessary.
      let started = await jsonRequest(widget.dataset.startUrl, startPayload);
      const chunkSize = Number(started.chunk_size_bytes || chunkSizeFallback);
      const total = Math.ceil(file.size / chunkSize);
      if (total !== startPayload.total) {
        throw new Error('اندازه قطعه آپلود با تنظیمات سرور هماهنگ نیست؛ صفحه را تازه کنید و دوباره تلاش کنید.');
      }

      for (let index = 0; index < total; index++) {
        const form = new FormData();
        form.append('upload_id', started.upload_id);
        form.append('index', String(index));
        form.append('chunk', file.slice(index * chunkSize, Math.min(file.size, (index + 1) * chunkSize)), file.name);

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
        setProgress(((index + 1) / total) * 92);
        setStatus('در حال ارسال فایل… بخش ' + (index + 1) + ' از ' + total);
      }

      const completed = await jsonRequest(widget.dataset.completeUrl, {
        upload_id: started.upload_id,
        total,
        filename: file.name,
      });
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
        } else {
          setStatus('تریلر بارگذاری شد و در صف پردازش قرار گرفت.');
        }
      } else {
        setStatus('ویدیو با موفقیت دریافت و در کتابخانه خصوصی ثبت شد. وضعیت اتصال ارائه‌دهنده: در انتظار قرارداد.');
        window.setTimeout(() => window.location.reload(), 1600);
      }
    } catch (error) {
      setStatus(error.message || 'بارگذاری فایل ناموفق بود.', true);
      startButton.disabled = false;
      fileInput.disabled = false;
    }
  });
});
