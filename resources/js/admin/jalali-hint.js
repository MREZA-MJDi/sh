// Shows the Jalali equivalent under every date / datetime-local input (value stays Gregorian for the server).
const fa = (s) => String(s).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
function toJalali(gy, gm, gd) {
  const g = [0, 31, 59, 90, 120, 151, 181, 212, 243, 273, 304, 334];
  const gy2 = gm > 2 ? gy + 1 : gy;
  let days = 355666 + 365 * gy + Math.floor((gy2 + 3) / 4) - Math.floor((gy2 + 99) / 100) + Math.floor((gy2 + 399) / 400) + gd + g[gm - 1];
  let jy = -1595 + 33 * Math.floor(days / 12053);
  days %= 12053;
  jy += 4 * Math.floor(days / 1461);
  days %= 1461;
  if (days > 365) { jy += Math.floor((days - 1) / 365); days = (days - 1) % 365; }
  const jm = days < 186 ? 1 + Math.floor(days / 31) : 7 + Math.floor((days - 186) / 30);
  const jd = 1 + (days < 186 ? days % 31 : (days - 186) % 30);
  return [jy, jm, jd];
}
const pad = (n) => String(n).padStart(2, '0');
function update(input, hint) {
  const m = /^(\d{4})-(\d{2})-(\d{2})(?:T(\d{2}:\d{2}))?/.exec(input.value || '');
  if (!m) { hint.textContent = ''; return; }
  const [jy, jm, jd] = toJalali(+m[1], +m[2], +m[3]);
  hint.textContent = '📅 ' + fa(`${jy}/${pad(jm)}/${pad(jd)}${m[4] ? ' ساعت ' + m[4] : ''}`);
}
function init(input) {
  if (input.dataset.jh) { input._jh(); return; }
  input.dataset.jh = '1';
  const hint = document.createElement('small');
  hint.className = 'adm-hint adm-jalali-hint';
  input.insertAdjacentElement('afterend', hint);
  const run = () => update(input, hint);
  input.addEventListener('input', run);
  input.addEventListener('change', run);
  input._jh = run;
  run();
}
const scan = () => document.querySelectorAll('input[type=date],input[type=datetime-local]').forEach(init);
document.addEventListener('DOMContentLoaded', scan);
document.addEventListener('click', () => setTimeout(scan, 50));
