// <button data-copy="https://…"> copies the value and shows a toast.
import { toast } from './toast.js';

document.addEventListener('click', async (e) => {
  const b = e.target.closest('[data-copy]');
  if (!b) return;
  try { await navigator.clipboard.writeText(b.dataset.copy); toast('لینک کپی شد'); }
  catch (_) { toast('کپی انجام نشد؛ لینک را دستی کپی کنید', 'info'); }
});
