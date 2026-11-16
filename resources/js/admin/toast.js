// window.admToast('message', 'ok'|'info'). Also: any element with data-toast="message" shows one on click.
export function toast(message, tone = 'ok') {
  const box = document.getElementById('adm-toasts');
  if (!box) return;
  const el = document.createElement('div');
  el.className = `adm-toast is-${tone}`;
  el.setAttribute('role', 'status');
  el.innerHTML = '<svg class="adm-icon" viewBox="0 0 24 24" aria-hidden="true"><use href="#i-check"/></svg><span></span>';
  el.lastChild.textContent = message;
  box.append(el);
  setTimeout(() => el.remove(), 3200);
}
window.admToast = toast;

document.addEventListener('click', (e) => {
  const t = e.target.closest('[data-toast]');
  if (t) toast(t.dataset.toast);
});

// server flash messages rendered by partials/flash.blade.php
document.querySelectorAll('[data-flash]').forEach((f) => toast(f.dataset.flash, f.dataset.tone || 'ok'));
