/* Product gallery: thumbnails, native modal viewer, keyboard controls and focus restoration. */
(function () {
  'use strict';
  document.querySelectorAll('.product-gallery').forEach(function (gallery) {
    var link = gallery.querySelector('.gallery-main');
    var main = gallery.querySelector('.gallery-main img');
    var caption = gallery.querySelector('.gallery-caption');
    var count = gallery.querySelector('.gallery-count');
    var thumbs = Array.from(gallery.querySelectorAll('.gallery-thumb'));
    var dialog = gallery.querySelector('dialog');
    if (!link || !main || !dialog) return;
    var viewer = dialog.querySelector('.gallery-viewer-image');
    var viewerCaption = dialog.querySelector('.gallery-viewer-caption');
    var viewerCount = dialog.querySelector('.gallery-viewer-count');
    var viewerThumbs = Array.from(dialog.querySelectorAll('.gallery-viewer-thumb'));
    var index = 0;
    function updateViewer() {
      viewer.src = link.href;
      viewer.alt = main.alt;
      viewerCaption.textContent = caption.textContent;
      viewerCaption.hidden = !caption.textContent;
      viewerCount.textContent = count.textContent;
      viewerThumbs.forEach(function (item, i) {
        item.classList.toggle('is-active', i === index);
        item.setAttribute('aria-current', i === index ? 'true' : 'false');
      });
      if (dialog.open && viewerThumbs[index]) viewerThumbs[index].scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'instant'});
    }
    function select(next) {
      if (!thumbs.length) return;
      index = (next + thumbs.length) % thumbs.length;
      var thumb = thumbs[index];
      main.src = thumb.dataset.src;
      main.alt = thumb.dataset.alt || '';
      link.href = thumb.dataset.full || thumb.dataset.src;
      link.setAttribute('aria-label', 'View larger: ' + main.alt);
      caption.textContent = thumb.dataset.caption || '';
      caption.hidden = !caption.textContent;
      count.textContent = (index + 1) + ' / ' + thumbs.length;
      thumbs.forEach(function (item, i) {
        item.classList.toggle('is-active', i === index);
        item.setAttribute('aria-current', i === index ? 'true' : 'false');
      });
      if (dialog.open) updateViewer();
      else thumb.scrollIntoView({block: 'nearest', inline: 'nearest', behavior: 'instant'});
    }
    thumbs.forEach(function (thumb, i) {
      thumb.addEventListener('click', function () { select(i); });
      thumb.addEventListener('keydown', function (event) {
        if (event.key !== 'ArrowRight' && event.key !== 'ArrowLeft') return;
        event.preventDefault();
        select(index + (event.key === 'ArrowRight' ? 1 : -1));
        thumbs[index].focus();
      });
    });
    viewerThumbs.forEach(function (thumb, i) {
      thumb.addEventListener('click', function () { select(i); });
    });
    link.addEventListener('click', function (event) {
      if (typeof dialog.showModal !== 'function') return; // Plain link still opens the full image.
      event.preventDefault();
      updateViewer();
      dialog.showModal();
      document.documentElement.classList.add('viewer-open');
      updateViewer();
      dialog.querySelector('.gallery-close').focus();
    });
    dialog.querySelector('.gallery-close').addEventListener('click', function () { dialog.close(); });
    dialog.querySelector('.gallery-prev').addEventListener('click', function () { select(index - 1); });
    dialog.querySelector('.gallery-next').addEventListener('click', function () { select(index + 1); });
    dialog.querySelector('.gallery-prev').hidden = thumbs.length < 2;
    dialog.querySelector('.gallery-next').hidden = thumbs.length < 2;
    dialog.addEventListener('keydown', function (event) {
      var onThumb = event.target.closest('.gallery-viewer-thumb');
      if (event.key === 'ArrowRight' || event.key === 'ArrowLeft' || (onThumb && (event.key === 'ArrowDown' || event.key === 'ArrowUp'))) {
        event.preventDefault(); select((onThumb ? Number(onThumb.dataset.index) : index) + (event.key === 'ArrowRight' || event.key === 'ArrowDown' ? 1 : -1));
        if (onThumb && viewerThumbs[index]) viewerThumbs[index].focus({preventScroll: true});
      }
    });
    dialog.addEventListener('click', function (event) {
      if (event.target === dialog || event.target.matches('.gallery-viewer, .gallery-viewer-stage')) dialog.close();
    });
    dialog.addEventListener('close', function () {
      document.documentElement.classList.remove('viewer-open'); link.focus({preventScroll: true});
    });
    [link, viewer].forEach(function (surface) {
      var start = null;
      var swiped = false;
      surface.addEventListener('touchstart', function (event) {
        swiped = false; // A new tap must not inherit a prior swipe that produced no click.
        start = event.touches.length === 1 ? {x: event.touches[0].clientX, y: event.touches[0].clientY} : null;
      }, {passive: true});
      surface.addEventListener('touchend', function (event) {
        if (!start || !event.changedTouches.length) return;
        var dx = event.changedTouches[0].clientX - start.x;
        var dy = event.changedTouches[0].clientY - start.y;
        start = null;
        if (thumbs.length > 1 && Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.5) {
          select(index + (dx < 0 ? 1 : -1)); swiped = true;
        }
      }, {passive: true});
      surface.addEventListener('click', function (event) {
        if (swiped) { event.preventDefault(); event.stopImmediatePropagation(); swiped = false; }
      }, true);
    });
  });
})();

/* Product/category reading: accurate sticky offsets and inquiry context. */
(function () {
  'use strict';
  var page = document.querySelector('.product-default, .category-standard, .contact-page');
  if (!page) return;
  var header = document.querySelector('.site-header');
  var nav = page.querySelector('.product-section-nav');
  var inquiry = page.querySelector('.product-inquiry');
  var readingLayout = page.querySelector('.product-reading-layout');
  var links = nav ? Array.from(nav.querySelectorAll('a')) : [];
  var sections = links.map(function (link) { return document.querySelector(link.getAttribute('href')); });
  // Reveal keyboard-focused labels within the horizontal track without moving the page.
  links.forEach(function (link) {
    link.addEventListener('focus', function () {
      var track = link.parentElement;
      var bounds = track.getBoundingClientRect();
      var item = link.getBoundingClientRect();
      track.scrollLeft += Math.min(0, item.left - bounds.left) + Math.max(0, item.right - bounds.right);
    });
  });
  var offset = 104;
  var inquiryTop = 104;
  var inquiryHeight = 0;
  var inquiryViewportTop = null;
  var inquiryScrollY = window.scrollY;
  function updateInquiry() {
    if (!inquiry) return;
    if (readingLayout && !readingLayout.classList.contains('no-guide') && innerWidth > 900 && inquiry.classList.contains('is-tall')) {
      // A long form travels with page scrolling between its top and bottom stops.
      // Use the actual previous position so direction changes and container edges
      // do not jump. Native sticky still owns the reading region's lower boundary.
      var previousTop = inquiryViewportTop === null ? inquiry.getBoundingClientRect().top : inquiryViewportTop;
      var bottomStop = Math.min(inquiryTop, innerHeight - inquiryHeight - 20);
      var top = Math.max(bottomStop, Math.min(inquiryTop, previousTop - (window.scrollY - inquiryScrollY)));
      inquiry.style.setProperty('--product-inquiry-scroll-top', top + 'px');
      inquiryViewportTop = inquiry.getBoundingClientRect().top;
    } else {
      inquiry.style.removeProperty('--product-inquiry-scroll-top');
      inquiryViewportTop = null;
    }
    inquiryScrollY = window.scrollY;
  }
  function measure() {
    offset = header ? header.getBoundingClientRect().height + (parseFloat(getComputedStyle(header).top) || 0) : 0;
    page.style.setProperty('--product-sticky-top', offset + 'px');
    // Align the painted top border with the navigation's painted bottom border.
    var navHeight = nav ? nav.getBoundingClientRect().height - (parseFloat(getComputedStyle(nav).borderBottomWidth) || 0) : 0;
    inquiryTop = offset + navHeight + (page.classList.contains('contact-page') ? 16 : 0);
    page.style.setProperty('--product-nav-height', navHeight + 'px');
    page.style.setProperty('--product-inquiry-top', inquiryTop + 'px');
    var pagePadding = parseFloat(getComputedStyle(document.documentElement).scrollPaddingTop) || 0;
    page.style.setProperty('--product-anchor-offset', Math.max(0, offset + (nav ? nav.offsetHeight : 0) + 20 - pagePadding) + 'px');
    if (inquiry && inquiry.firstElementChild) {
      var card = inquiry.firstElementChild;
      inquiryHeight = card.getBoundingClientRect().height;
      inquiry.classList.toggle('is-tall', inquiryHeight > innerHeight - inquiryTop - 20);
      inquiryViewportTop = null;
      inquiryScrollY = window.scrollY;
      updateInquiry();
    }
    page.querySelectorAll('.lead-time-scroll').forEach(function (table) {
      var hint = table.parentElement.querySelector('.table-scroll-note');
      if (hint) hint.hidden = table.scrollWidth <= table.clientWidth + 1;
    });
  }
  function updateNav() {
    updateInquiry();
    // Both columns leave the reading region together. Short descriptions reach the
    // inquiry's containing-block limit earlier than the small navigation alone.
    var release = 0;
    if (nav && inquiry && readingLayout && innerWidth > 900 && !readingLayout.classList.contains('no-guide')) {
      var inquiryStop = parseFloat(getComputedStyle(inquiry).top) || 0;
      release = Math.min(0, readingLayout.getBoundingClientRect().bottom - inquiry.offsetHeight - inquiryStop);
    }
    if (nav) nav.style.transform = release ? 'translateY(' + release + 'px)' : '';
    // Keep the hero clean: reveal the desktop inquiry when entering the reading area.
    if (inquiry && readingLayout) {
      var enteringDetails = readingLayout.getBoundingClientRect().top <= offset + (nav ? nav.offsetHeight : 0) + 24;
      inquiry.classList.toggle('is-pending', innerWidth > 900 && !enteringDetails && !inquiry.contains(document.activeElement));
    }
    var active = 0;
    sections.forEach(function (section, index) {
      if (section && section.getBoundingClientRect().top <= offset + (nav ? nav.offsetHeight : 0) + 24) active = index;
    });
    links.forEach(function (link, index) {
      if (index === active) link.setAttribute('aria-current', 'true');
      else link.removeAttribute('aria-current');
    });
  }
  measure(); updateNav();
  window.addEventListener('resize', function () { measure(); updateNav(); });
  window.addEventListener('load', measure);
  var queued = false;
  window.addEventListener('scroll', function () {
    if (queued) return;
    queued = true;
    requestAnimationFrame(function () { updateNav(); queued = false; });
  }, {passive: true});
  if ('ResizeObserver' in window) {
    var observer = new ResizeObserver(measure);
    if (header) observer.observe(header);
    if (nav) observer.observe(nav);
    if (inquiry) observer.observe(inquiry);
  }
})();

