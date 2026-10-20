document.addEventListener('DOMContentLoaded', () => {
  let shared, placeholder;
  const openers = new WeakMap();
  const slot = document.getElementById('axis-inquiry-slot');
  if (slot && !slot.querySelector('form')) {
    shared = document.querySelector('main .fluentform');
    if (shared) {
      placeholder = document.createComment('Shared inquiry form');
      shared.before(placeholder);
    }
  }
  document.querySelectorAll('[data-axis-open]').forEach(button => {
    button.setAttribute('aria-haspopup', 'dialog');
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.axisOpen);
      if (!dialog || dialog.open) return;
      if (dialog.id === 'axis-quote' && shared && slot) slot.append(shared);
      openers.set(dialog, button);
      dialog.showModal();
      document.documentElement.classList.add('axis-dialog-open');
      button.setAttribute('aria-expanded', 'true');
    });
  });
  document.querySelectorAll('.axis-dialog').forEach(dialog => {
    dialog.querySelector('.axis-close')?.addEventListener('click', () => dialog.close());
    dialog.addEventListener('close', () => {
      if (dialog.id === 'axis-quote' && shared && placeholder) placeholder.after(shared);
      if (!document.querySelector('.axis-dialog[open]')) document.documentElement.classList.remove('axis-dialog-open');
      const opener = openers.get(dialog);
      if (opener?.isConnected) {
        opener.setAttribute('aria-expanded', 'false');
        opener.focus({preventScroll: true});
      }
    });
    dialog.addEventListener('click', event => {
      if (event.target !== dialog) return;
      const bounds = dialog.getBoundingClientRect();
      if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
    });
  });
});
