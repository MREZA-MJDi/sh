// Drag to reorder: <div data-sortable> <div data-sort-item> <span data-grip> … </div> </div>
// After a drop the container fires "sorted" with detail.order (array of data-id) — post it to your backend.
import { toast } from './toast.js';

document.querySelectorAll('[data-sortable]').forEach((box) => {
  let dragged = null;
  box.addEventListener('pointerdown', (e) => {
    const item = e.target.closest('[data-sort-item]');
    if (item) item.draggable = !!e.target.closest('[data-grip]');
  });
  box.addEventListener('dragstart', (e) => { dragged = e.target.closest('[data-sort-item]'); dragged?.classList.add('is-dragging'); e.dataTransfer.effectAllowed = 'move'; });
  box.addEventListener('dragover', (e) => {
    const over = e.target.closest('[data-sort-item]');
    if (!dragged || !over || over === dragged || over.parentElement !== dragged.parentElement) return;
    e.preventDefault();
    const after = e.clientY > over.getBoundingClientRect().top + over.offsetHeight / 2;
    over.parentElement.insertBefore(dragged, after ? over.nextSibling : over);
  });
  box.addEventListener('dragend', () => {
    if (!dragged) return;
    dragged.classList.remove('is-dragging');
    dragged.draggable = false;
    const order = [...dragged.parentElement.children].map((c) => c.dataset.id);
    box.dispatchEvent(new CustomEvent('sorted', { detail: { order }, bubbles: true }));
    toast('ترتیب جدید اعمال شد');
    dragged = null;
  });
});
