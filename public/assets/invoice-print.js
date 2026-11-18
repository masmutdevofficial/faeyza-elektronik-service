// Keep invoice printing independent from the admin/customer form scripts.
const printHelp = document.querySelector('[data-print-help]');
let printObserved = false;
const markPrinting = () => {
  printObserved = true;
  document.querySelectorAll('[data-print]').forEach(button => { button.dataset.printState = 'printing'; });
};
window.addEventListener('beforeprint', markPrinting);
window.addEventListener('afterprint', () => {
  printObserved = true;
  document.querySelectorAll('[data-print]').forEach(button => { button.dataset.printState = 'complete'; });
});
const printMedia = typeof window.matchMedia === 'function' ? window.matchMedia('print') : null;
printMedia?.addEventListener?.('change', event => { if (event.matches) markPrinting(); });
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => {
  printObserved = false;
  button.dataset.printState = 'requested';
  const showHelp = () => { if (printHelp) printHelp.open = true; };
  if (typeof window.print !== 'function') { showHelp(); return; }
  try { window.print(); }
  catch { showHelp(); return; }
  window.setTimeout(() => { if (!printObserved) showHelp(); }, 800);
}));
