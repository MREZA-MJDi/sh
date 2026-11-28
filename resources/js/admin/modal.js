// Modal CRUD helper.
//   Open:    <button data-modal-open="dialog-id" data-title="..." data-fill='{"name":"x"}' data-action="/url" data-method="PUT" data-name="item name">
//   Create:  same without data-fill / data-action  → form is reset and posts to its own action.
//   Demo:    forms with [data-demo] are intercepted and only show a toast. Remove the attribute when the backend exists.
import { toast } from './toast.js';
import { syncReveal } from './reveal.js';

function fill(form, data) {
  Object.entries(data).forEach(([key, value]) => {
    const arrayTarget = form.querySelector('[data-array-field="' + key + '"]');
    if (Array.isArray(value) && arrayTarget) {
      arrayTarget.querySelectorAll('[data-generated-selection]').forEach((node) => node.remove());
      const labels = Array.isArray(data[key + '_labels']) ? data[key + '_labels'] : [];
      value.forEach((item, index) => {
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = key + '[]';
        hidden.value = item;
        hidden.dataset.generatedSelection = 'true';

        const chip = document.createElement('span');
        chip.className = 'adm-chip';
        chip.textContent = labels[index] || ('#' + item);
        chip.dataset.generatedSelection = 'true';

        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'adm-btn adm-btn-line adm-btn-sm';
        remove.textContent = 'حذف';
        remove.dataset.generatedSelection = 'true';
        remove.addEventListener('click', () => {
          hidden.remove();
          chip.remove();
          remove.remove();
        });

        arrayTarget.append(hidden, chip, remove);
      });
    }
    form.querySelectorAll(`[name="${key}"],[name="${key}[]"]`).forEach((el) => {
      if (el.type === 'file') return;
      if (el.type === 'hidden' && Array.from(form.querySelectorAll('input[type="checkbox"]')).some((checkbox) => checkbox.name === key)) {
        el.value = value === true || value === 1 || value === '1' ? '1' : '0';
      } else if (el.type === 'checkbox' || el.type === 'radio') {
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
  form.querySelectorAll('[data-array-field] [data-generated-selection]').forEach((node) => node.remove());
  form.querySelectorAll('[name="password"],[name="password_confirmation"]').forEach((field) => {
    field.required = !Boolean(trigger.dataset.fill);
  });
  form.dataset.createAction ??= form.getAttribute('action') || '';
  form.setAttribute('action', trigger.dataset.action || form.dataset.createAction);

  const retryAction = form.elements.namedItem('_editor_action');
  if (retryAction) retryAction.value = form.getAttribute('action');

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

document.addEventListener('DOMContentLoaded', () => {
  const retry = document.querySelector('dialog.adm-modal [data-editor-retry-action]');
  if (!retry) return;

  const form = retry.form;
  const dialog = form.closest('dialog');
  const intendedMethod = form.querySelector('[data-editor-retry-method]').value;
  const method = form.querySelector('[data-method-field]');
  const trigger = Array.from(document.querySelectorAll('[data-modal-open][data-action]')).find(
    (candidate) => candidate.dataset.modalOpen === dialog.id &&
      candidate.dataset.action === retry.value && (candidate.dataset.method || 'POST') === intendedMethod
  );
  const isDefault = retry.value === form.getAttribute('action') && intendedMethod === method.dataset.default;
  if (!isDefault && !trigger) return;

  form.dataset.createAction = form.getAttribute('action');
  form.setAttribute('action', retry.value);
  form.elements.namedItem('_editor_action').value = retry.value;
  method.value = intendedMethod;
  if (trigger?.dataset.title) {
    const heading = dialog.querySelector('[data-modal-title]');
    heading.dataset.default = heading.textContent;
    heading.textContent = trigger.dataset.title;
  }
  syncReveal(form);
  dialog.showModal();
});
