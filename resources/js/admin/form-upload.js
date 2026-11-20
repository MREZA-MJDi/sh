const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const uploadForms = 'form[enctype="multipart/form-data"]';

document.addEventListener('submit', (event) => {
  const form = event.target.closest(uploadForms);
  if (!form || form.dataset.uploading === 'true') return;

  const files = [...form.querySelectorAll('input[type="file"]')].filter((input) => input.files?.length);
  if (!files.length) return;

  event.preventDefault();
  form.dataset.uploading = 'true';

  const submit = event.submitter || form.querySelector('button[type="submit"],input[type="submit"]');
  const originalLabel = submit?.textContent || '';
  if (submit) {
    submit.disabled = true;
    submit.textContent = 'در حال ارسال فایل…';
  }

  let feedback = form.querySelector('[data-form-upload-feedback]');
  if (!feedback) {
    feedback = document.createElement('div');
    feedback.className = 'adm-form-upload-feedback';
    feedback.dataset.formUploadFeedback = '';
    feedback.setAttribute('role', 'status');
    feedback.setAttribute('aria-live', 'polite');
    const footer = form.querySelector('.adm-modal-foot') || form;
    footer.insertBefore(feedback, footer.firstChild);
  }

  feedback.hidden = false;
  feedback.textContent = 'در حال آماده‌سازی فایل…';

  const bar = document.createElement('div');
  bar.className = 'adm-form-upload-track';
  const fill = document.createElement('i');
  bar.append(fill);
  feedback.replaceChildren(document.createTextNode('در حال آماده‌سازی فایل…'), bar);

  const request = new XMLHttpRequest();
  request.open((form.method || 'POST').toUpperCase(), form.action, true);
  request.withCredentials = true;
  request.setRequestHeader('Accept', 'text/html');
  request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
  if (csrf()) request.setRequestHeader('X-CSRF-TOKEN', csrf());

  request.upload.addEventListener('progress', (progressEvent) => {
    if (!progressEvent.lengthComputable) {
      feedback.firstChild.textContent = 'در حال ارسال فایل…';
      return;
    }
    const percent = Math.round((progressEvent.loaded / progressEvent.total) * 100);
    fill.style.width = percent + '%';
    feedback.firstChild.textContent = percent < 100
      ? 'در حال ارسال فایل… ' + percent + '٪'
      : 'فایل دریافت شد؛ در حال ذخیره‌سازی…';
  });

  request.addEventListener('load', () => {
    if (request.status >= 200 && request.status < 400) {
      fill.style.width = '100%';
      feedback.firstChild.textContent = 'فایل با موفقیت دریافت شد؛ صفحه در حال به‌روزرسانی است.';
      window.location.assign(request.responseURL || window.location.href);
      return;
    }

    form.dataset.uploading = 'false';
    if (submit) {
      submit.disabled = false;
      submit.textContent = originalLabel || 'تلاش دوباره';
    }
    feedback.firstChild.textContent = request.status === 413
      ? 'حجم فایل از محدودیت سرور بیشتر است. فایل کوچک‌تری انتخاب کن.'
      : 'ارسال فایل کامل نشد. اتصال را بررسی کن و دوباره تلاش کن.';
  });

  request.addEventListener('error', () => {
    form.dataset.uploading = 'false';
    if (submit) {
      submit.disabled = false;
      submit.textContent = originalLabel || 'تلاش دوباره';
    }
    feedback.firstChild.textContent = 'ارتباط قطع شد. فایل انتخاب‌شده باقی مانده؛ دوباره تلاش کن.';
  });

  request.send(new FormData(form));
});
