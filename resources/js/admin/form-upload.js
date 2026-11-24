const uploadForms = 'form[enctype="multipart/form-data"]';

document.addEventListener('submit', (event) => {
  const form = event.target.closest(uploadForms);
  if (!form || event.defaultPrevented) return;

  if (form.dataset.uploading === 'true') {
    event.preventDefault();
    return;
  }
  if (![...form.querySelectorAll('input[type="file"]')].some((input) => input.files?.length)) return;

  form.dataset.uploading = 'true';
  form.setAttribute('aria-busy', 'true');

  const feedback = document.createElement('p');
  feedback.className = 'adm-form-upload-feedback';
  feedback.dataset.formUploadFeedback = '';
  feedback.setAttribute('role', 'status');
  feedback.textContent = 'در حال ارسال فایل…';
  (form.querySelector('.adm-modal-foot') || form).prepend(feedback);
});

window.addEventListener('pageshow', () => {
  document.querySelectorAll('form[data-uploading="true"]').forEach((form) => {
    delete form.dataset.uploading;
    form.removeAttribute('aria-busy');
    form.querySelector('[data-form-upload-feedback]')?.remove();
  });
});
