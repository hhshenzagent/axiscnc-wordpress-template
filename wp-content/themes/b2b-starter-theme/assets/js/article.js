/* global document, window, ResizeObserver */
/* Native article anchors and the shared inquiry form follow the visible global header. */
(function () {
  'use strict';
  var page = document.querySelector('.article-page');
  if (!page) return;
  var header = document.querySelector('.site-header');
  var links = Array.from(page.querySelectorAll('.article-contents a[href^="#"]'));
  var ids = Array.from(new Set(links.map(function (link) { return link.hash.slice(1); })));
  var headings = ids.map(function (id) { return document.getElementById(id); }).filter(function (heading) { return heading && page.contains(heading); });
  var pending = false;
  var lastTop = -1;
  var lastOffset = -1;
  var lastActive = null;

  function update() {
    pending = false;
    var top = Math.ceil(Math.max(0, header ? header.getBoundingClientRect().bottom : 0)) + 24;
    var scrollPadding = parseFloat(window.getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
    var offset = Math.max(0, top + 8 - scrollPadding);
    var active = headings.length ? headings[0].id : null;
    headings.forEach(function (heading) {
      if (heading.getBoundingClientRect().top <= top + 40) active = heading.id;
    });
    if (top !== lastTop) {
      page.style.setProperty('--article-sticky-top', top + 'px');
      lastTop = top;
    }
    if (offset !== lastOffset) {
      page.style.setProperty('--article-anchor-offset', offset + 'px');
      lastOffset = offset;
    }
    if (active !== lastActive) {
      links.forEach(function (link) {
        if (link.hash.slice(1) === active) link.setAttribute('aria-current', 'location');
        else link.removeAttribute('aria-current');
      });
      lastActive = active;
    }
  }
  function schedule() {
    if (pending) return;
    pending = true;
    window.requestAnimationFrame(update);
  }
  window.addEventListener('scroll', schedule, {passive: true});
  window.addEventListener('resize', schedule);
  window.addEventListener('hashchange', schedule);
  window.addEventListener('load', schedule);
  if (header && 'ResizeObserver' in window) new ResizeObserver(schedule).observe(header);
  update();
})();

