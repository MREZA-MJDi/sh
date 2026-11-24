// Conditional fields: <div data-show-if="access=paid"> … </div>
// Multiple accepted values: "type=book|product".

export function syncReveal(form) {
  if (!(form instanceof HTMLFormElement)) return;

  const data = new FormData(form);

  form.querySelectorAll('[data-show-if]').forEach((element) => {
    const [name, values = ''] = element.dataset.showIf.split('=');
    const selectedValues = [
      ...data.getAll(name),
      ...data.getAll(`${name}[]`),
    ].map(String);

    const shouldShow = values
      .split('|')
      .some((value) => selectedValues.includes(value));

    element.classList.toggle('adm-hide', !shouldShow);
  });
}

// Admin pages are server-rendered. Keep their content visible from the first
// paint; progressive enhancement must never hide the page while JS initializes.
const handleConditionalFields = (event) => {
  const target = event.target;

  if (!(target instanceof Element)) return;

  const form = target.closest('form');

  if (form) syncReveal(form);
};

document.addEventListener('change', handleConditionalFields);

// Vite loads this module as a deferred module after the document has been parsed.
document.querySelectorAll('form').forEach(syncReveal);
