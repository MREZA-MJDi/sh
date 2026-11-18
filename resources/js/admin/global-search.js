/* Filter the visible workspace without replacing its server-side search or pagination. */
const search = document.querySelector('[data-admin-global-search]');
if (search) {
  const selectors = [
    'main .adm-table tbody tr',
    'main [data-filter-list] > *',
    'main .adm-session',
    'main .adm-todo > a',
    'main .adm-card',
  ];
  const items = [...new Set(selectors.flatMap((selector) => [...document.querySelectorAll(selector)]))];
  search.addEventListener('input', () => {
    const term = search.value.trim().toLocaleLowerCase();
    items.forEach((item) => {
      const matches = !term || item.textContent.toLocaleLowerCase().includes(term);
      item.classList.toggle('is-global-search-hidden', !matches);
    });
  });
}
