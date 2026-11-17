document.querySelectorAll('form').forEach(form => {
  form.addEventListener('submit', () => {
    if (!form.checkValidity()) return;
    form.querySelectorAll('button[type="submit"],button:not([type])').forEach(button => {
      button.disabled = true;
      button.setAttribute('aria-busy', 'true');
      button.dataset.originalHtml = button.innerHTML;
      button.textContent = 'Memproses…';
    });
  });
});
window.addEventListener('pageshow', () => {
  document.querySelectorAll('button[data-original-html]').forEach(button => {
    button.disabled = false; button.innerHTML = button.dataset.originalHtml;
    button.removeAttribute('aria-busy');
  });
});
const menu = document.querySelector('.menu-toggle');
menu?.addEventListener('click', () => {
  const open = document.body.classList.toggle('nav-open');
  menu.setAttribute('aria-expanded', String(open));
  menu.setAttribute('aria-label', open ? 'Tutup navigasi' : 'Buka navigasi');
});
document.addEventListener('keydown', event => {
  if (event.key === 'Escape') { document.body.classList.remove('nav-open'); menu?.setAttribute('aria-expanded', 'false'); }
});
document.querySelectorAll('[data-custom-select]').forEach(select => {
  const field = document.querySelector('[data-custom-field="' + select.dataset.customSelect + '"]');
  const update = () => { const show = select.value === 'Lainnya'; field.hidden = !show; field.querySelector('input').required = show; };
  select.addEventListener('change', update); update();
});
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
