/* global document */
/* Progressive enhancement: the photo links still open originals without JavaScript. */
(function () {
    'use strict';
    document.querySelectorAll('.about-gallery').forEach(function (gallery) {
        var links = Array.from(gallery.querySelectorAll('.about-gallery-link'));
        var dialog = gallery.querySelector('dialog');
        if (!links.length || !dialog || typeof dialog.showModal !== 'function') return;
        var image = dialog.querySelector('.about-gallery-viewer-image');
        var caption = dialog.querySelector('.about-gallery-caption');
        var count = dialog.querySelector('.about-gallery-count');
        var close = dialog.querySelector('.about-gallery-close');
        var prev = dialog.querySelector('.about-gallery-prev');
        var next = dialog.querySelector('.about-gallery-next');
        var index = 0;
        var opener = null;
        prev.hidden = next.hidden = links.length < 2;
        dialog.querySelector('.about-gallery-stage').classList.toggle('is-single', links.length < 2);
        function select(i) {
            index = (i + links.length) % links.length;
            image.src = links[index].href;
            image.alt = links[index].dataset.alt || '';
            caption.textContent = links[index].dataset.caption || '';
            caption.hidden = !caption.textContent;
            count.textContent = (index + 1) + ' / ' + links.length;
        }
        links.forEach(function (link, i) {
            link.addEventListener('click', function (event) {
                if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.button !== 0) return;
                event.preventDefault();
                opener = link;
                select(i);
                dialog.showModal();
                document.documentElement.classList.add('about-gallery-open');
                close.focus();
            });
        });
        close.addEventListener('click', function () { dialog.close(); });
        prev.addEventListener('click', function () { select(index - 1); });
        next.addEventListener('click', function () { select(index + 1); });
        dialog.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                event.preventDefault();
                select(index + (event.key === 'ArrowRight' ? 1 : -1));
            }
        });
        dialog.addEventListener('click', function (event) {
            if (event.target !== dialog) return;
            var bounds = dialog.getBoundingClientRect();
            if (event.clientX < bounds.left || event.clientX > bounds.right || event.clientY < bounds.top || event.clientY > bounds.bottom) dialog.close();
        });
        dialog.addEventListener('close', function () {
            document.documentElement.classList.remove('about-gallery-open');
            if (opener) opener.focus({preventScroll: true});
        });
        var start = null;
        image.addEventListener('touchstart', function (event) {
            start = event.touches.length === 1 ? {x: event.touches[0].clientX, y: event.touches[0].clientY} : null;
        }, {passive: true});
        image.addEventListener('touchend', function (event) {
            if (!start || !event.changedTouches.length) return;
            var dx = event.changedTouches[0].clientX - start.x;
            var dy = event.changedTouches[0].clientY - start.y;
            start = null;
            if (Math.abs(dx) > 48 && Math.abs(dx) > Math.abs(dy) * 1.5) select(index + (dx < 0 ? 1 : -1));
        }, {passive: true});
        image.addEventListener('touchcancel', function () { start = null; }, {passive: true});
    });
}());

