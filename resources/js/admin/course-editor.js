
// Native 16:9 course-cover cropper. No external CDN or dependency required.
const initCourseCoverCropper = () => {
  const input = document.querySelector('[data-course-cover-input]');
  if (!input) return;

  const style = document.createElement('style');
  style.textContent = [
    '.course-cover-cropper{width:min(920px,calc(100% - 24px));max-width:920px;border:0;border-radius:18px;padding:0;box-shadow:0 24px 80px rgba(15,23,42,.32)}',
    '.course-cover-cropper::backdrop{background:rgba(15,23,42,.68);backdrop-filter:blur(3px)}',
    '.course-cover-cropper__inner{padding:20px;display:grid;gap:16px}',
    '.course-cover-cropper__stage{width:100%;aspect-ratio:16/9;background:#101827;border-radius:12px;overflow:hidden;touch-action:none;cursor:grab}',
    '.course-cover-cropper__stage:active{cursor:grabbing}',
    '.course-cover-cropper__stage canvas{width:100%;height:100%;display:block}',
    '.course-cover-cropper__controls{display:flex;align-items:center;gap:12px;flex-wrap:wrap}',
    '.course-cover-cropper__controls input{flex:1;min-width:160px;accent-color:var(--admin-primary,#3156c9)}',
    '.course-cover-cropper__actions{display:flex;justify-content:flex-end;gap:8px;flex-wrap:wrap}',
    '@media(max-width:600px){.course-cover-cropper__inner{padding:13px}.course-cover-cropper__actions>*{flex:1}}'
  ].join('');
  document.head.append(style);

  const dialog = document.createElement('dialog');
  dialog.className = 'course-cover-cropper';
  dialog.setAttribute('aria-labelledby', 'course-cover-cropper-title');
  dialog.innerHTML =
    '<div class="course-cover-cropper__inner" dir="rtl">' +
      '<div><h2 id="course-cover-cropper-title" style="margin:0 0 5px;font-size:18px">تنظیم تصویر دوره</h2>' +
      '<p style="margin:0;color:var(--admin-muted,#64748b);font-size:13px">کادر تصویر ۱۶:۹ است؛ تصویر را بکش یا بزرگ‌نمایی کن تا بهترین بخش داخل کادر قرار بگیرد.</p></div>' +
      '<div class="course-cover-cropper__stage"><canvas width="960" height="540" aria-label="پیش‌نمایش برش تصویر"></canvas></div>' +
      '<label class="course-cover-cropper__controls"><span>بزرگ‌نمایی</span><input type="range" min="1" max="3" step="0.01" value="1" data-cover-zoom><output data-cover-zoom-value>۱×</output></label>' +
      '<div class="course-cover-cropper__actions"><button type="button" class="adm-btn adm-btn-line" data-cover-cancel>انصراف</button>' +
      '<button type="button" class="adm-btn adm-btn-primary" data-cover-apply>تأیید برش و استفاده از تصویر</button></div>' +
    '</div>';
  document.body.append(dialog);

  const canvas = dialog.querySelector('canvas');
  const context = canvas.getContext('2d');
  const zoomInput = dialog.querySelector('[data-cover-zoom]');
  const zoomValue = dialog.querySelector('[data-cover-zoom-value]');
  const applyButton = dialog.querySelector('[data-cover-apply]');
  const cancelButton = dialog.querySelector('[data-cover-cancel]');
  const stage = dialog.querySelector('.course-cover-cropper__stage');
  let image = null;
  let objectUrl = null;
  let zoom = 1;
  let offsetX = 0;
  let offsetY = 0;
  let drag = null;

  const dimensions = () => {
    const scale = Math.max(canvas.width / image.naturalWidth, canvas.height / image.naturalHeight) * zoom;
    return { width: image.naturalWidth * scale, height: image.naturalHeight * scale };
  };
  const clampOffset = () => {
    const size = dimensions();
    offsetX = Math.min(0, Math.max(canvas.width - size.width, offsetX));
    offsetY = Math.min(0, Math.max(canvas.height - size.height, offsetY));
  };
  const draw = () => {
    if (!image) return;
    clampOffset();
    const size = dimensions();
    context.clearRect(0, 0, canvas.width, canvas.height);
    context.drawImage(image, offsetX, offsetY, size.width, size.height);
  };
  const releaseSource = () => {
    if (objectUrl) URL.revokeObjectURL(objectUrl);
    objectUrl = null;
    image = null;
  };
  const closeCropper = () => {
    if (dialog.open) dialog.close();
    releaseSource();
  };
  const openCropper = (file) => {
    releaseSource();
    objectUrl = URL.createObjectURL(file);
    image = new Image();
    image.onload = () => {
      zoom = 1;
      zoomInput.value = '1';
      zoomValue.textContent = '۱×';
      const size = dimensions();
      offsetX = (canvas.width - size.width) / 2;
      offsetY = (canvas.height - size.height) / 2;
      draw();
      if (!dialog.open) dialog.showModal();
    };
    image.onerror = () => {
      input.value = '';
      releaseSource();
      window.alert('خواندن تصویر انتخاب‌شده ممکن نشد؛ لطفاً فایل JPG، PNG یا WebP معتبر انتخاب کن.');
    };
    image.src = objectUrl;
  };

  input.addEventListener('change', (event) => {
    if (input.dataset.cropperApplying === '1') {
      delete input.dataset.cropperApplying;
      return;
    }
    const file = input.files?.[0];
    if (!file) return;
    if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
      event.stopImmediatePropagation();
      input.value = '';
      window.alert('فرمت تصویر باید JPG، PNG یا WebP باشد.');
      return;
    }
    event.stopImmediatePropagation();
    openCropper(file);
  }, true);

  zoomInput.addEventListener('input', () => {
    if (!image) return;
    const previousZoom = zoom;
    const centerX = (canvas.width / 2 - offsetX) / previousZoom;
    const centerY = (canvas.height / 2 - offsetY) / previousZoom;
    zoom = Number(zoomInput.value);
    offsetX = canvas.width / 2 - centerX * (zoom / previousZoom);
    offsetY = canvas.height / 2 - centerY * (zoom / previousZoom);
    zoomValue.textContent = zoom.toFixed(1) + '×';
    draw();
  });

  stage.addEventListener('pointerdown', (event) => {
    if (!image) return;
    drag = { x: event.clientX, y: event.clientY, offsetX, offsetY };
    stage.setPointerCapture(event.pointerId);
  });
  stage.addEventListener('pointermove', (event) => {
    if (!drag) return;
    const rect = canvas.getBoundingClientRect();
    offsetX = drag.offsetX + (event.clientX - drag.x) * (canvas.width / rect.width);
    offsetY = drag.offsetY + (event.clientY - drag.y) * (canvas.height / rect.height);
    draw();
  });
  const endDrag = () => { drag = null; };
  stage.addEventListener('pointerup', endDrag);
  stage.addEventListener('pointercancel', endDrag);
  cancelButton.addEventListener('click', () => {
    input.value = '';
    closeCropper();
  });

  applyButton.addEventListener('click', () => {
    if (!image) return;
    applyButton.disabled = true;
    const output = document.createElement('canvas');
    output.width = 1600;
    output.height = 900;
    const outputContext = output.getContext('2d');
    const scaleRatio = output.width / canvas.width;
    const size = dimensions();
    const outputScale = Math.max(output.width / image.naturalWidth, output.height / image.naturalHeight) * zoom;
    outputContext.drawImage(image, offsetX * scaleRatio, offsetY * scaleRatio,
      image.naturalWidth * outputScale, image.naturalHeight * outputScale);
    output.toBlob((blob) => {
      applyButton.disabled = false;
      if (!blob) {
        window.alert('ساخت تصویر برش‌خورده ناموفق بود؛ دوباره تلاش کن.');
        return;
      }
      const cropped = new File([blob], 'course-cover.webp', { type: blob.type || 'image/webp', lastModified: Date.now() });
      const transfer = new DataTransfer();
      transfer.items.add(cropped);
      input.files = transfer.files;
      const name = input.closest('.adm-field')?.querySelector('[data-upload-name]');
      if (name) name.textContent = cropped.name + ' · برش ۱۶:۹ آماده شد';
      input.dataset.cropperApplying = '1';
      input.dispatchEvent(new Event('change', { bubbles: true }));
      closeCropper();
    }, 'image/webp', 0.9);
  });

  dialog.addEventListener('cancel', () => {
    input.value = '';
    releaseSource();
  });
};

initCourseCoverCropper();


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
const targetRequiresSession = (widget) => widget.dataset.requireSession === 'true';

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
  const uploadZone = fileInput.closest('.adm-upload');
  if (uploadZone) uploadZone.insertAdjacentElement('afterend', preview);
  else fileInput.insertAdjacentElement('afterend', preview);
  let previewUrl = null;

  fileInput.addEventListener('change', () => {
    if (previewUrl) URL.revokeObjectURL(previewUrl);
    previewUrl = null;
    previewVideo.removeAttribute('src');
    previewVideo.load();
    const selected = fileInput.files?.[0];
    startButton.disabled = false;
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
  const setControlsDisabled = (disabled, courseSelect, classroomSelect, sessionSelect) => {
    startButton.disabled = disabled;
    fileInput.disabled = disabled;
    if (courseSelect) courseSelect.disabled = disabled;
    if (classroomSelect) classroomSelect.disabled = disabled;
    if (sessionSelect) sessionSelect.disabled = disabled;
  };

  const classroomField = widget.querySelector('[data-upload-classroom]');
  const sessionField = widget.querySelector('[data-upload-session]');
  const filterSessions = () => {
    if (!sessionField) return;
    const classroomId = classroomField?.value || '';
    Array.from(sessionField.options).forEach((option) => {
      const optionClassroom = option.dataset.classroomId || '';
      const visible = !option.value || !classroomId || optionClassroom === classroomId;
      option.hidden = !visible;
      option.disabled = !visible;
    });
    if (sessionField.selectedOptions[0]?.disabled) sessionField.value = '';
  };
  classroomField?.addEventListener('change', filterSessions);
  sessionField?.addEventListener('change', () => {
    const selected = sessionField.selectedOptions[0];
    if (selected?.dataset.classroomId && classroomField && !classroomField.value) {
      classroomField.value = selected.dataset.classroomId;
    }
    filterSessions();
  });
  filterSessions();

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

    const classroomSelect = widget.querySelector('[data-upload-classroom]');
    const sessionSelect = widget.querySelector('[data-upload-session]');
    const classSessionId = Number(sessionSelect?.value || 0);
    if (targetRequiresSession(widget) && !classSessionId) {
      setStatus('ابتدا کلاس و سپس جلسه موردنظر را انتخاب کنید.', true);
      sessionSelect?.focus();
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

    const resumeKey = ['sh-video-upload', target, courseId, classSessionId || 0, file.name, file.size, file.lastModified].join(':');
    setControlsDisabled(true, courseSelect, classroomSelect, sessionSelect);
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
          const classroomId = classroomSelect?.value;
          const externalId = widget.querySelector('[data-upload-external]')?.value?.trim();
          if (classroomId) startPayload.classroom_id = Number(classroomId);
          if (classSessionId) startPayload.class_session_id = classSessionId;
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
        setStatus('فایل دریافت شد؛ در حال پردازش تریلر…');
        const statusUrl = widget.dataset.statusUrl;
        if (statusUrl) {
          for (let attempt = 0; attempt < 90; attempt++) {
            await sleep(2000);
            let state;
            try {
              state = await jsonRequest(statusUrl, {upload_id: started.upload_id});
            } catch (pollError) {
              if (pollError.status && pollError.status < 500) throw pollError;
              continue;
            }
            if (state.status === 'ready') {
              if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
              setStatus('تریلر آماده نمایش است. صفحه در حال به‌روزرسانی است.');
              window.location.reload();
              return;
            }
            if (state.status === 'failed') {
              if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
              throw new Error(state.error || 'پردازش تریلر ناموفق بود.');
            }
          }
          setStatus('پردازش تریلر هنوز ادامه دارد؛ وضعیت را کمی بعد دوباره بررسی کنید.');
          setControlsDisabled(false, courseSelect, classroomSelect, sessionSelect);
        } else {
          if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
          setStatus('تریلر بارگذاری شد و در صف پردازش قرار گرفت.');
        }
      } else {
        if (widget.dataset.statusUrl) {
          setStatus('آپلود کامل شد؛ فایل در فضای خصوصی در حال پردازش و آماده‌سازی پخش است…');
          for (let attempt = 0; attempt < 90; attempt++) {
            await sleep(2000);
            let state;
            try {
              state = await jsonRequest(widget.dataset.statusUrl, {upload_id: started.upload_id});
            } catch (pollError) {
              if (pollError.status && pollError.status < 500) throw pollError;
              continue;
            }
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
          setStatus('پردازش ویدیو هنوز ادامه دارد؛ با انتخاب همین فایل و تلاش دوباره، وضعیت پردازش را ادامه می‌دهید.');
          setControlsDisabled(false, courseSelect, classroomSelect, sessionSelect);
        } else {
          if (widget.dataset.resumeUrl) localStorage.removeItem(resumeKey);
          setStatus(completed.status === 'ready'
            ? 'ویدیو با موفقیت در فضای خصوصی ذخیره شد و آماده پخش است.'
            : 'ویدیو ثبت شد؛ وضعیت پردازش یا اتصال آن در فهرست ویدیوها نمایش داده می‌شود.');
          window.setTimeout(() => window.location.reload(), 1600);
        }
      }
    } catch (error) {
      const resumable = started?.upload_id && !completedUpload;
      const suffix = resumable
        ? ' نشست آپلود حفظ شد؛ با انتخاب همین فایل و زدن دوباره دکمه، از بخش‌های دریافت‌شده ادامه می‌دهید.'
        : '';
      setStatus((error.message || 'بارگذاری فایل ناموفق بود.') + suffix, true);
      setControlsDisabled(false, courseSelect, classroomSelect, sessionSelect);
    }
  });
});


const syncResourceVisibility = (form) => {
  const classroomSelect = form?.querySelector('[data-resource-classroom]');
  const visibilitySelect = form?.querySelector('[data-resource-visibility]');
  const publicOption = visibilitySelect?.querySelector('option[value="public"]');
  if (!classroomSelect || !visibilitySelect || !publicOption) return;

  publicOption.disabled = Boolean(classroomSelect.value);
  if (classroomSelect.value && visibilitySelect.value === 'public') {
    visibilitySelect.value = 'enrolled';
  }
};

document.addEventListener('change', (event) => {
  if (!event.target.matches?.('[data-resource-classroom]')) return;
  syncResourceVisibility(event.target.closest('form'));
});

document.addEventListener('reset', (event) => {
  Promise.resolve().then(() => syncResourceVisibility(event.target));
});
