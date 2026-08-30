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

function openWorkflowForm(formCard) {
  if (!formCard) return;
  formCard.hidden = false;
  formCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  setTimeout(() => formCard.querySelector('input:not([type="hidden"]), select, textarea')?.focus(), 250);
}

document.querySelectorAll('[data-workflow-open]').forEach((button) => {
  button.addEventListener('click', () => openWorkflowForm(document.getElementById(button.dataset.workflowOpen)));
});

function addWorkflowButton(listCard, formCard) {
  if (listCard.querySelector('[data-auto-workflow-open]')) return;
  const heading = listCard.querySelector(':scope > .card-heading') || listCard.querySelector(':scope > h2');
  if (!heading) return;
  const button = document.createElement('button');
  button.type = 'button';
  button.className = 'small-button';
  button.dataset.autoWorkflowOpen = '';
  button.textContent = 'Novo registro';
  button.addEventListener('click', () => openWorkflowForm(formCard));
  if (heading.matches('h2')) {
    const wrapper = document.createElement('div');
    wrapper.className = 'workflow-heading';
    heading.before(wrapper);
    wrapper.append(heading, button);
  } else {
    heading.append(button);
  }
}

function setupListFirst(formCard, listCard, container) {
  if (!formCard || !listCard || formCard.dataset.workflowReady !== undefined) return;
  formCard.dataset.workflowReady = '';
  formCard.classList.add('workflow-form-card');
  listCard.classList.add('workflow-list-card');
  container.classList.add('list-first-layout');
  container.insertBefore(listCard, formCard);
  formCard.hidden = !formCard.hasAttribute('data-form-open');
  addWorkflowButton(listCard, formCard);

  const close = document.createElement('button');
  close.type = 'button';
  close.className = 'secondary-button small-button workflow-cancel';
  close.textContent = 'Cancelar';
  close.addEventListener('click', () => {
    formCard.hidden = true;
    listCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
  (formCard.querySelector('.actions') || formCard.querySelector('form'))?.append(close);
}

document.querySelectorAll('section.grid.cols-2').forEach((container) => {
  const cards = [...container.children].filter((child) => child.classList?.contains('card'));
  const formCard = cards.find((card) => card.querySelector(':scope > form'));
  const listCard = cards.find((card) => card !== formCard && card.querySelector('table'));
  if (formCard && listCard) {
    setupListFirst(formCard, listCard, container);
    return;
  }

  const formCards = cards.filter((card) => card.querySelector(':scope > form'));
  if (formCards.length < 2) return;
  const listCards = [];
  let sibling = container.nextElementSibling;
  while (sibling && listCards.length < formCards.length) {
    if (sibling.matches('section.card') && sibling.querySelector('table')) listCards.push(sibling);
    sibling = sibling.nextElementSibling;
  }
  if (listCards.length !== formCards.length) return;
  formCards.forEach((card, index) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'list-first-stack';
    container.before(wrapper);
    wrapper.append(card, listCards[index]);
    setupListFirst(card, listCards[index], wrapper);
  });
  container.remove();
});

const contentFrame = document.querySelector('.content-frame');
if (contentFrame) {
  const sections = [...contentFrame.children];
  sections.forEach((formCard, index) => {
    const listCard = sections[index + 1];
    if (formCard.matches?.('section.card') && formCard.querySelector(':scope > form') && listCard?.matches?.('section.card') && listCard.querySelector('table')) {
      const container = document.createElement('div');
      container.className = 'list-first-stack';
      formCard.before(container);
      container.append(formCard, listCard);
      setupListFirst(formCard, listCard, container);
    }
  });

  const financeForm = contentFrame.querySelector('.finance-entry-card');
  const financeList = [...contentFrame.querySelectorAll('.finance-table-card')].at(-1);
  if (financeForm && financeList) {
    const financeStack = document.createElement('div');
    financeStack.className = 'list-first-stack';
    financeForm.before(financeStack);
    financeStack.append(financeForm, financeList);
    setupListFirst(financeForm, financeList, financeStack);
  }
}
