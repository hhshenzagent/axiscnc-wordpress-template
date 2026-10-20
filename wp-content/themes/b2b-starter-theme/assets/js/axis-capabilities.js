document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('[data-axis-capabilities]').forEach(module => {
    const choices = module.querySelector('.axis-capabilities-choices');
    const panelGroup = module.querySelector('.axis-capabilities-panels');
    const buttons = [...module.querySelectorAll('.axis-capabilities-choice')];
    const panels = buttons.map(button => module.querySelector(`#${button.getAttribute('aria-controls')}`));
    if (!choices || !panelGroup || !buttons.length || panels.some(panel => !panel)) return;
    const narrow = window.matchMedia('(max-width: 900px)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    // Preserve reading order: each mobile answer follows its own control.
    function arrange() {
      panels.forEach((panel, index) => {
        if (narrow.matches) buttons[index].after(panel);
        else panelGroup.append(panel);
      });
      panelGroup.hidden = narrow.matches;
      module.classList.toggle('is-inline-accordion', narrow.matches);
    }
    function activate(index, focus = false) {
      buttons.forEach((button, i) => button.setAttribute('aria-expanded', String(i === index)));
      panels.forEach((panel, i) => {
        panel.hidden = i !== index;
        panel.classList.remove('is-changing');
      });
      requestAnimationFrame(() => panels[index].classList.add('is-changing'));
      if (focus) buttons[index].focus({preventScroll: narrow.matches});
      const position = buttons[index].getBoundingClientRect();
      const header = document.querySelector('.site-header');
      const headerBottom = Math.max(0, header?.getBoundingClientRect().bottom || 0);
      if (narrow.matches && (position.top < headerBottom + 12 || position.bottom > window.innerHeight)) {
        buttons[index].scrollIntoView({block: 'start', behavior: reducedMotion.matches ? 'instant' : 'smooth'});
      }
    }
    arrange();
    narrow.addEventListener('change', arrange);
    buttons.forEach((button, index) => {
      button.addEventListener('click', () => activate(index));
      button.addEventListener('keydown', event => {
        let next;
        if (['ArrowDown', 'ArrowRight'].includes(event.key)) next = (index + 1) % buttons.length;
        else if (['ArrowUp', 'ArrowLeft'].includes(event.key)) next = (index - 1 + buttons.length) % buttons.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = buttons.length - 1;
        else return;
        event.preventDefault();
        activate(next, true);
      });
    });
  });
});
