const audienceCounts = Object.fromEntries(
  [...document.querySelectorAll('[data-notice-audience] option')].map((option) => [
    option.value,
    Number((option.textContent.match(/[0-9,٬]+/) || ['0'])[0].replace(/[٬,]/g, '')),
  ]),
);

document.addEventListener('input', (event) => {
  const form = event.target.closest('.adm-notice-form');
  if (!form) return;

  const title = form.querySelector('[data-notice-title]')?.value.trim();
  const message = form.querySelector('[data-notice-message]')?.value.trim();
  const url = form.querySelector('[data-notice-url]')?.value.trim();
  const action = form.querySelector('[data-notice-action]')?.value.trim();

  const previewTitle = document.querySelector('[data-notice-preview-title]');
  const previewMessage = document.querySelector('[data-notice-preview-message]');
  const previewAction = document.querySelector('[data-notice-preview-action]');

  if (previewTitle) previewTitle.textContent = title || 'عنوان اعلان اینجا نمایش داده می‌شود';
  if (previewMessage) previewMessage.textContent = message || 'متن پیام را بنویس تا پیش‌نمایش آن را اینجا ببینی.';
  if (previewAction) {
    previewAction.textContent = action || 'مشاهده جزئیات';
    previewAction.hidden = !url;
  }
});

document.addEventListener('change', (event) => {
  if (!event.target.matches('[data-notice-audience]')) return;
  const option = event.target.selectedOptions[0];
  const count = Number((option?.textContent.match(/[0-9,٬]+/) || ['0'])[0].replace(/[٬,]/g, ''));
  const output = document.querySelector('[data-notice-recipient-count]');
  if (output) output.textContent = new Intl.NumberFormat('fa-IR').format(count);
});
