document.querySelectorAll('[data-confirm]').forEach((button) => {
  button.addEventListener('click', (event) => {
    if (!confirm(button.dataset.confirm)) {
      event.preventDefault();
    }
  });
});
