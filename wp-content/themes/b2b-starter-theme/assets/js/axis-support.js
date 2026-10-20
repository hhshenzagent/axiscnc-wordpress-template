/* Native horizontal scrolling keeps CMS cards usable without JavaScript. */
(() => {
  document.querySelectorAll('.axis-support-carousel').forEach((carousel) => {
    const track = carousel.querySelector('.axis-support-grid');
    const controls = carousel.querySelector('.axis-support-controls');
    const pause = carousel.querySelector('[data-support-pause]');
    const cards = [...track.children];
    if (cards.length < 2) return;
    const reduced = window.matchMedia('(prefers-reduced-motion: reduce)');
    const copies = cards.map((card) => {
      const copy = card.cloneNode(true);
      copy.dataset.supportClone = '';
      copy.setAttribute('aria-hidden', 'true');
      copy.inert = true;
      copy.querySelectorAll('img').forEach((image) => { image.loading = 'eager'; });
      track.append(copy);
      return copy;
    });
    let distance = 0, position = 0, frame = 0, previous = 0, timer;
    let visible = false, hovering = false, focusing = false, touching = false;
    let interacting = false, paused = false, fits = false;
    const canMove = () => !fits && visible && !document.hidden && !reduced.matches &&
      !hovering && !focusing && !touching && !interacting && !paused;
    const tick = (now) => {
      if (!canMove()) { frame = 0; previous = 0; return; }
      position = (position + Math.min(now - previous, 50) * 0.032) % distance;
      previous = now;
      track.scrollLeft = position;
      frame = requestAnimationFrame(tick);
    };
    const sync = () => {
      if (!canMove()) {
        cancelAnimationFrame(frame); frame = 0; previous = 0;
      } else if (!frame) {
        position = track.scrollLeft % distance;
        previous = performance.now();
        frame = requestAnimationFrame(tick);
      }
    };
    const measure = () => {
      const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      distance = cards[cards.length - 1].offsetLeft + cards[cards.length - 1].offsetWidth - cards[0].offsetLeft + gap;
      fits = distance - gap <= track.clientWidth + 1;
      copies.forEach((copy) => { copy.hidden = fits; });
      controls.hidden = fits;
      position = fits ? 0 : track.scrollLeft % distance;
      track.scrollLeft = position;
      sync();
    };
    const rest = () => {
      interacting = true; clearTimeout(timer); sync();
      timer = setTimeout(() => { interacting = false; sync(); }, 3500);
    };
    carousel.addEventListener('mouseenter', () => { hovering = true; sync(); });
    carousel.addEventListener('mouseleave', () => { hovering = false; sync(); });
    carousel.addEventListener('focusin', () => { focusing = true; sync(); });
    carousel.addEventListener('focusout', () => {
      requestAnimationFrame(() => { focusing = carousel.contains(document.activeElement); sync(); });
    });
    track.addEventListener('pointerdown', () => { touching = true; sync(); });
    window.addEventListener('pointerup', () => { if (touching) { touching = false; rest(); } });
    track.addEventListener('pointercancel', () => { touching = false; rest(); });
    track.addEventListener('wheel', rest, { passive: true });
    track.addEventListener('keydown', rest);
    pause.addEventListener('click', () => {
      paused = !paused;
      pause.setAttribute('aria-pressed', String(paused));
      pause.textContent = paused ? 'Play' : 'Pause';
      sync();
    });
    const step = (direction) => {
      rest();
      const gap = parseFloat(getComputedStyle(track).columnGap) || 0;
      const width = cards[0].offsetWidth + gap;
      let target = track.scrollLeft + direction * width;
      if (target < 0) target += distance;
      if (target >= distance) target -= distance;
      track.scrollTo({ left: target, behavior: reduced.matches ? 'instant' : 'smooth' });
    };
    carousel.querySelector('[data-support-prev]').addEventListener('click', () => step(-1));
    carousel.querySelector('[data-support-next]').addEventListener('click', () => step(1));
    new IntersectionObserver(([entry]) => { visible = entry.isIntersecting; sync(); }, { threshold: 0.05 }).observe(track);
    new ResizeObserver(measure).observe(track);
    document.addEventListener('visibilitychange', sync);
    reduced.addEventListener('change', sync);
    measure();
  });
})();
