/* Primary navigation: progressive disclosure, hover, touch and keyboard. */
(function () {
  'use strict';
  var header = document.querySelector('.site-header');
  var toggle = header ? header.querySelector('.nav-toggle') : null;
  var panel = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;
  if (!header || !panel || !toggle) return;
  var desktop = window.matchMedia('(min-width: 1101px)');
  var hover = window.matchMedia('(hover: hover) and (pointer: fine)');
  var items = [];

  function setOpen(item, open) {
    item.node.classList.toggle('is-submenu-open', open);
    item.button.setAttribute('aria-expanded', String(open));
    if (!open) items.forEach(function (child) {
      if (child !== item && item.node.contains(child.node)) {
        child.node.classList.remove('is-submenu-open');
        child.button.setAttribute('aria-expanded', 'false');
      }
    });
  }
  function closeSubmenus() {
    items.forEach(function (item) { setOpen(item, false); });
  }
  function positionSubmenu(item) {
    if (!desktop.matches) return;
    item.node.classList.remove('submenu-align-right');
    var row = item.node.getBoundingClientRect();
    var nested = item.node.parentElement.classList.contains('sub-menu');
    item.submenu.style.setProperty('--submenu-height', Math.max(80, window.innerHeight - (nested ? 16 : row.bottom) - 16) + 'px');
    if (nested) {
      var width = item.submenu.getBoundingClientRect().width;
      var left = row.right;
      if (left + width > window.innerWidth - 16) {
        left = row.left - width;
        item.node.classList.add('submenu-align-right');
      }
      left = Math.max(16, Math.min(left, window.innerWidth - width - 16));
      var height = item.submenu.getBoundingClientRect().height;
      item.submenu.style.setProperty('--submenu-left', left + 'px');
      item.submenu.style.setProperty('--submenu-top', Math.max(16, Math.min(row.top, window.innerHeight - height - 16)) + 'px');
    } else if (item.submenu.getBoundingClientRect().right > window.innerWidth - 16) {
      item.node.classList.add('submenu-align-right');
    }
  }
  function openSubmenu(item) {
    // Keep the ancestor path open; close only branches outside that path.
    items.forEach(function (other) {
      if (other !== item && !other.node.contains(item.node)) setOpen(other, false);
    });
    items.forEach(function (ancestor) {
      if (ancestor.node.contains(item.node)) { setOpen(ancestor, true); positionSubmenu(ancestor); }
    });
    setOpen(item, true);
    positionSubmenu(item);
  }
  function closeMenu() {
    closeSubmenus();
    panel.classList.remove('open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open menu');
  }
  function focusFirst(item) {
    var link = item.submenu.querySelector('a');
    if (link) link.focus();
  }

  panel.querySelectorAll('li').forEach(function (node, index) {
    var submenu = Array.from(node.children).find(function (child) { return child.tagName === 'UL' && child.querySelector('a'); });
    var link = Array.from(node.children).find(function (child) { return child.tagName === 'A'; });
    if (!submenu || !link) return;
    var button = document.createElement('button');
    button.type = 'button';
    button.className = 'submenu-toggle';
    button.setAttribute('aria-expanded', 'false');
    button.setAttribute('aria-label', 'Toggle submenu: ' + link.textContent.trim());
    submenu.id = submenu.id || panel.id + '-submenu-' + index;
    button.setAttribute('aria-controls', submenu.id);
    node.insertBefore(button, submenu);
    node.classList.add('has-submenu');
    var item = {node: node, submenu: submenu, link: link, button: button};
    items.push(item);
    button.addEventListener('click', function () {
      if (button.getAttribute('aria-expanded') === 'true') setOpen(item, false);
      else openSubmenu(item);
    });
    node.addEventListener('pointerenter', function () {
      if (desktop.matches && hover.matches) openSubmenu(item);
    });
    node.addEventListener('pointerleave', function () {
      if (desktop.matches && hover.matches && !submenu.contains(document.activeElement)) setOpen(item, false);
    });
    node.addEventListener('focusout', function (event) {
      if (!node.contains(event.relatedTarget)) setOpen(item, false);
    });
    [link, button].forEach(function (control) {
      control.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowDown' && event.key !== 'ArrowRight') return;
        event.preventDefault();
        openSubmenu(item);
        focusFirst(item);
      });
    });
    node.addEventListener('keydown', function (event) {
      if ((event.key !== 'Escape' && event.key !== 'ArrowLeft') || button.getAttribute('aria-expanded') !== 'true') return;
      event.preventDefault();
      event.stopPropagation();
      setOpen(item, false);
      button.focus();
    });
  });
  panel.classList.add('is-enhanced');

  toggle.addEventListener('click', function () {
    var open = !panel.classList.contains('open');
    closeMenu();
    panel.classList.toggle('open', open);
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
  });
  panel.addEventListener('click', function (event) {
    if (event.target.closest('a')) closeMenu();
  });
  document.addEventListener('keydown', function (event) {
    if (event.key !== 'Escape') return;
    if (panel.classList.contains('open')) { closeMenu(); toggle.focus(); }
    else closeSubmenus();
  });
  document.addEventListener('click', function (event) {
    if (!header.contains(event.target)) closeMenu();
  });
  header.addEventListener('focusout', function (event) {
    if (!header.contains(event.relatedTarget)) closeMenu();
  });
  desktop.addEventListener('change', closeMenu);
  // Fixed flyouts escape the first panel's scrolling box and follow its rows.
  function repositionOpen() {
    items.forEach(function (item) {
      if (item.button.getAttribute('aria-expanded') === 'true') positionSubmenu(item);
    });
  }
  window.addEventListener('resize', repositionOpen);
  window.addEventListener('scroll', repositionOpen, {passive: true});
  panel.addEventListener('scroll', repositionOpen, true);

  if ('IntersectionObserver' in window) {
    var sentinel = document.createElement('div');
    sentinel.setAttribute('aria-hidden', 'true');
    sentinel.className = 'header-sentinel';
    header.parentNode.insertBefore(sentinel, header);
    new IntersectionObserver(function (entries) {
      header.classList.toggle('is-elevated', !entries[0].isIntersecting);
    }).observe(sentinel);
  }
})();

