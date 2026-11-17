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
  if (event.key === 'Escape') { document.body.classList.remove('nav-open'); menu?.setAttribute('aria-expanded', 'false'); menu?.setAttribute('aria-label', 'Buka navigasi'); }
});
// Reveal invalid required fields even when their contact section is collapsed.
document.querySelectorAll('details').forEach(details => {
  details.addEventListener('invalid', () => { details.open = true; }, true);
});
document.querySelectorAll('[data-custom-select]').forEach(select => {
  const field = document.querySelector('[data-custom-field="' + select.dataset.customSelect + '"]');
  const update = () => { const show = select.value === 'Lainnya'; field.hidden = !show; field.querySelector('input').required = show; };
  select.addEventListener('change', update); update();
});
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
const customerPicker = document.querySelector('[data-customer-picker]');
if (customerPicker) {
  const form = customerPicker.form;
  const preview = form.querySelector('[data-customer-preview]');
  const editor = form.querySelector('[data-customer-editor]');
  const contactFields = {customer_name: 'customerName', contact_phone: 'customerPhone', address: 'customerAddress'};
  const updateCustomer = (fill) => {
    const option = customerPicker.selectedOptions[0];
    const selected = !!option?.value;
    if (fill) Object.entries(contactFields).forEach(([name,key]) => { form.elements[name].value = selected ? option.dataset[key] : ''; });
    preview.hidden = !selected;
    editor.hidden = !selected;
    preview.querySelector('[data-contact-name]').textContent = form.elements.customer_name.value;
    preview.querySelector('[data-contact-phone]').textContent = form.elements.contact_phone.value;
    preview.querySelector('[data-contact-address]').textContent = form.elements.address.value;
    if (fill) editor.open = false;
  };
  customerPicker.addEventListener('change', () => updateCustomer(true));
  Object.keys(contactFields).forEach(name => form.elements[name].addEventListener('input', () => updateCustomer(false)));
  updateCustomer(false);
}
const signatureSetting = document.querySelector('[data-signature-setting]');
const serviceStatus = document.querySelector('select[name="status"]');
if (signatureSetting && serviceStatus) {
  const updateSignature = () => {
    const complete = ['Selesai', 'Sudah Diambil'].includes(serviceStatus.value);
    signatureSetting.hidden = !complete;
    signatureSetting.querySelectorAll('input').forEach(input => { input.disabled = !complete; });
  };
  serviceStatus.addEventListener('change', updateSignature);
  updateSignature();
}
