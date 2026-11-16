// Conditional fields: <div data-show-if="access=paid"> … </div>   (several values: "type=book|product")
export function syncReveal(form) {
  const data = new FormData(form);
  form.querySelectorAll('[data-show-if]').forEach((el) => {
    const [name, values] = el.dataset.showIf.split('=');
    const current = [...data.getAll(name), ...data.getAll(`${name}[]`)].map(String);
    el.classList.toggle('adm-hide', !values.split('|').some((v) => current.includes(v)));
  });
}

document.addEventListener('change', (e) => { const f = e.target.closest('form'); if (f) syncReveal(f); });
document.querySelectorAll('form').forEach(syncReveal);
