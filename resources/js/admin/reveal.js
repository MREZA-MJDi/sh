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


// Subtle, one-time entrance motion for cards and work queues across admin pages.
const revealTargets = document.querySelectorAll(
  '.adm-stat, .adm-panel, .adm-card, .adm-work-item, .adm-communication-item, .adm-online-card',
);
const reduceMotion = window.matchMedia?.('(prefers-reduced-motion: reduce)').matches ?? false;

if (!reduceMotion && 'IntersectionObserver' in window) {
  const revealObserver = new IntersectionObserver((entries, observer) => {
    entries.forEach((entry) => {
      if (!entry.isIntersecting) return;
      entry.target.classList.remove('adm-reveal-pending');
      entry.target.classList.add('adm-reveal-visible');
      observer.unobserve(entry.target);
    });
  }, { threshold: 0.08, rootMargin: '0px 0px -24px 0px' });

  revealTargets.forEach((element, index) => {
    element.classList.add('adm-reveal-pending');
    element.style.setProperty('--adm-reveal-delay', `${Math.min(index % 6, 5) * 38}ms`);
    revealObserver.observe(element);
  });
}
