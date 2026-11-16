// Modal CRUD helper.
//   Open:    <button data-modal-open="dialog-id" data-title="..." data-fill='{"name":"x"}' data-action="/url" data-method="PUT" data-name="item name">
//   Create:  same without data-fill / data-action  → form is reset and posts to its own action.
//   Demo:    forms with [data-demo] are intercepted and only show a toast. Remove the attribute when the backend exists.
import { toast } from './toast.js';
import { syncReveal } from './reveal.js';

function fill(form, data) {
  Object.entries(data).forEach(([key, value]) => {
    form.querySelectorAll(`[name="${key}"],[name="${key}[]"]`).forEach((el) => {
      if (el.type === 'file') return;
      if (el.type === 'checkbox' || el.type === 'radio') {
        if (Array.isArray(value)) el.checked = value.map(String).includes(el.value);
        else if (el.type === 'checkbox' && el.value === '1') el.checked = value === true || value === 1 || value === '1';
        else el.checked = String(value) === el.value;
      } else el.value = value ?? '';
    });
    form.querySelectorAll(`[data-bind="${key}"]`).forEach((el) => (el.textContent = value));
  });
}

function open(trigger) {
  const dialog = document.getElementById(trigger.dataset.modalOpen);
  if (!dialog) return;
  const form = dialog.querySelector('form');
  const heading = dialog.querySelector('[data-modal-title]');

  form.reset();
  form.dataset.createAction ??= form.getAttribute('action') || '';
  form.setAttribute('action', trigger.dataset.action || form.dataset.createAction);

  const method = form.querySelector('[data-method-field]');
  if (method) method.value = trigger.dataset.method || method.dataset.default;

  if (heading) {
    heading.dataset.default ??= heading.textContent;
    heading.textContent = trigger.dataset.title || heading.dataset.default;
  }
  dialog.querySelectorAll('[data-modal-name]').forEach((el) => (el.textContent = trigger.dataset.name || ''));

  if (trigger.dataset.fill) fill(dialog, JSON.parse(trigger.dataset.fill));
  syncReveal(form);
  dialog.showModal();
}

document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-modal-open]');
  if (trigger) return open(trigger);
  if (e.target.closest('[data-modal-close]')) return e.target.closest('dialog').close();
  if (e.target.matches('dialog.adm-modal')) e.target.close(); // backdrop click
});

document.addEventListener('submit', (e) => {
  const form = e.target;
  if (!form.hasAttribute('data-demo')) return;
  e.preventDefault();
  form.closest('dialog')?.close();
  toast(form.dataset.demoMessage || 'ذخیره شد (نمایشی — بدون ارتباط با بک‌اند)');
});
