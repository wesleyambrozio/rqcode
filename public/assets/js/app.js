document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!confirm(button.dataset.confirm)) event.preventDefault();
  });
});

const root = document.documentElement;
const themeButton = document.querySelector('[data-theme-toggle]');

function setTheme(theme) {
  root.dataset.theme = theme;
  localStorage.setItem('rqcode-theme', theme);
  if (themeButton) {
    const dark = theme === 'dark';
    themeButton.setAttribute('aria-label', dark ? 'Ativar modo claro' : 'Ativar modo escuro');
    themeButton.title = dark ? 'Ativar modo claro' : 'Ativar modo escuro';
  }
}

themeButton?.addEventListener('click', () => {
  setTheme(root.dataset.theme === 'dark' ? 'light' : 'dark');
});

setTheme(root.dataset.theme || 'light');

document.querySelectorAll('[data-menu-toggle]').forEach((button) => {
  button.addEventListener('click', () => document.body.classList.toggle('menu-open'));
});

document.querySelectorAll('.nav a').forEach((link) => {
  link.addEventListener('click', () => document.body.classList.remove('menu-open'));
});

const recurringToggle = document.querySelector('[data-recurring-toggle]');
const recurringFields = document.querySelector('[data-recurring-fields]');
const singleStatus = document.querySelector('.non-recurring-field');

function refreshRecurringFields() {
  if (!recurringToggle || !recurringFields) return;
  recurringFields.hidden = !recurringToggle.checked;
  if (singleStatus) singleStatus.hidden = recurringToggle.checked;
}

recurringToggle?.addEventListener('change', refreshRecurringFields);
refreshRecurringFields();

const energyDialog = document.querySelector('[data-energy-dialog]');
const energyFields = energyDialog ? {
  hours: energyDialog.querySelector('[data-energy-hours]'),
  watts: energyDialog.querySelector('[data-energy-watts]'),
  rate: energyDialog.querySelector('[data-energy-rate]'),
  margin: energyDialog.querySelector('[data-energy-margin]'),
  kwh: energyDialog.querySelector('[data-energy-kwh]'),
  total: energyDialog.querySelector('[data-energy-total]')
} : null;

function calculateEnergy() {
  if (!energyFields) return { kwh: 0, total: 0 };
  const hours = Math.max(0, Number(energyFields.hours.value) || 0);
  const watts = Math.max(0, Number(energyFields.watts.value) || 0);
  const rate = Math.max(0, Number(energyFields.rate.value) || 0);
  const margin = Math.max(0, Number(energyFields.margin.value) || 0);
  const kwh = watts * hours / 1000;
  const total = kwh * rate * (1 + margin / 100);
  energyFields.kwh.textContent = `${kwh.toLocaleString('pt-BR', { minimumFractionDigits: 3, maximumFractionDigits: 3 })} kWh`;
  energyFields.total.textContent = total.toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
  return { kwh, total, hours };
}

document.querySelector('[data-energy-open]')?.addEventListener('click', () => {
  const minutes = Number(document.querySelector('[data-print-minutes]')?.value) || 0;
  if (minutes > 0) energyFields.hours.value = (minutes / 60).toFixed(2);
  calculateEnergy();
  energyDialog.showModal();
});
energyDialog?.querySelectorAll('input').forEach((input) => input.addEventListener('input', calculateEnergy));
energyDialog?.querySelector('[data-energy-apply]')?.addEventListener('click', () => {
  const result = calculateEnergy();
  const cost = document.querySelector('[data-energy-cost]');
  const minutes = document.querySelector('[data-print-minutes]');
  if (cost) cost.value = result.total.toFixed(2);
  if (minutes) minutes.value = Math.round(result.hours * 60);
  energyDialog.close();
});

const payableToggle = document.querySelector('[data-payable-toggle]');
const payableDate = document.querySelector('[data-payable-date]');
function refreshPayableDate() { if (payableDate) payableDate.hidden = !payableToggle?.checked; }
payableToggle?.addEventListener('change', refreshPayableDate);
refreshPayableDate();
