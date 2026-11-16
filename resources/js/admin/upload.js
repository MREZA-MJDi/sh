// Drop-zone feedback for <label class="adm-upload"><input type="file">…<small data-upload-name>
const zone = (el) => el.closest('.adm-upload');

document.addEventListener('change', (e) => {
  if (e.target.type !== 'file') return;
  const z = zone(e.target);
  if (!z) return;
  const label = z.querySelector('[data-upload-name]');
  label.dataset.default ??= label.textContent;
  const file = e.target.files[0];
  label.textContent = file ? `${file.name} — ${(file.size / 1048576).toFixed(1)} مگابایت` : label.dataset.default;
  z.classList.toggle('has-file', !!file);
});

['dragenter', 'dragover'].forEach((t) => document.addEventListener(t, (e) => zone(e.target)?.classList.add('is-drag')));
['dragleave', 'drop'].forEach((t) => document.addEventListener(t, (e) => zone(e.target)?.classList.remove('is-drag')));
document.addEventListener('reset', (e) => {
  e.target.querySelectorAll?.('.adm-upload').forEach((z) => {
    z.classList.remove('has-file');
    const l = z.querySelector('[data-upload-name]');
    if (l?.dataset.default) l.textContent = l.dataset.default;
  });
}, true);
