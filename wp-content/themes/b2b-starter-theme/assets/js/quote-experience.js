(() => {
  const dialog = document.getElementById('axis-rfq-dialog');
  if (!dialog) return;
  const slot = document.getElementById('axis-rfq-slot');
  const id = dialog.dataset.formId;
  let opener, placeholder, wrapper, scrollY = 0, bodyStyle = '';
  const decorate = form => {
    form.classList.add('axis-compact-form');
    ['full_name', 'email', 'company', 'country'].forEach(name => {
      form.querySelector(`[name="${name}"]`)?.closest('.ff-el-group')?.classList.add('axis-half-field');
    });
    const message = form.querySelector('textarea');
    message?.addEventListener('input', e => { if (e.isTrusted) message.dataset.axisDraftEdited = 'true'; });
  };
  document.querySelectorAll(`form[data-form_id="${id}"]`).forEach(decorate);
  const isQuoteLink = link => {
    let url; try { url = new URL(link.href, location.href); } catch { return false; }
    if (url.origin !== location.origin) return false;
    const text = (link.textContent + ' ' + (link.getAttribute('aria-label') || '')).trim();
    if (/^(?:contact(?:\s+us)?|open contact page)\s*[↗→]?$/i.test(text)) return false;
    const destination = /\/(?:contact|request-a-quote)\/?$/.test(url.pathname) || /^(?:#product-inquiry|#inquiry|#contact-form|#quote)$/.test(url.hash);
    return destination && (/quote|quotation|inquiry|discuss|review|start.*project|send.*drawing|prepare.*brief|talk.*(?:part|project|team)/i.test(text) || link.classList.contains('button'));
  };
  const open = trigger => {
    if (dialog.open) return;
    const form = document.querySelector(`form[data-form_id="${id}"]`);
    if (!form) return;
    wrapper = form.closest('.fluentform');
    if (!wrapper) return;
    scrollY = window.scrollY;
    opener = trigger;
    if (!slot.contains(wrapper)) {
      placeholder = document.createElement('div');
      placeholder.className = 'axis-form-placeholder';
      placeholder.style.height = `${wrapper.getBoundingClientRect().height}px`;
      placeholder.setAttribute('aria-hidden', 'true');
      wrapper.before(placeholder);
      slot.append(wrapper);
    }
    const message = form.querySelector('textarea');
    if (message && message.dataset.axisDraftEdited !== 'true') {
      const url = new URL(trigger.href || location.href, location.href);
      const context = url.searchParams.get('context') || url.searchParams.get('solution') || url.searchParams.get('capability') || document.querySelector('main h1')?.innerText.replace(/\s+/g, ' ');
      const material = url.searchParams.get('material') || trigger.dataset.briefMaterial;
      message.value = [context && `Project context: ${context}`, material && material !== 'Not sure yet' && `Material to discuss: ${material}`, '', 'Requirements:'].filter(x => x !== false && x !== undefined).join('\n') + '\n';
      message.dispatchEvent(new Event('input', {bubbles:true}));
      message.dispatchEvent(new Event('change', {bubbles:true}));
    }
    bodyStyle = document.body.getAttribute('style') || '';
    const pageWidth = document.documentElement.clientWidth;
    Object.assign(document.body.style, {position:'fixed', top:`-${scrollY}px`, left:'0', right:'auto', width:`${pageWidth}px`});
    document.documentElement.classList.add('axis-rfq-open');
    trigger.setAttribute('aria-expanded', 'true');
    dialog.showModal();
    dialog.scrollTop = 0;
    form.querySelector('input:not([type=hidden]),textarea')?.focus({preventScroll:true});
  };
  document.querySelectorAll('a[href]').forEach(link => {
    if (!isQuoteLink(link)) return;
    link.dataset.axisQuote = '';
    link.setAttribute('aria-haspopup', 'dialog');
    link.setAttribute('aria-controls', dialog.id);
  });
  document.addEventListener('click', event => {
    const trigger = event.target.closest('[data-axis-quote]');
    if (!trigger || event.defaultPrevented || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey) return;
    event.preventDefault();
    open(trigger);
  });
  dialog.querySelector('.axis-rfq-close').addEventListener('click', event => {
    event.preventDefault();
    event.stopPropagation();
    dialog.close();
  });
  dialog.addEventListener('click', event => {
    if (event.target !== dialog) return;
    const r = dialog.getBoundingClientRect();
    if (event.clientX < r.left || event.clientX > r.right || event.clientY < r.top || event.clientY > r.bottom) dialog.close();
  });
  dialog.addEventListener('close', () => {
    if (placeholder?.isConnected && wrapper) { placeholder.replaceWith(wrapper); placeholder = null; }
    if (bodyStyle) document.body.setAttribute('style', bodyStyle); else document.body.removeAttribute('style');
    document.documentElement.classList.remove('axis-rfq-open');
    window.scrollTo({top:scrollY, behavior:'instant'});
    opener?.setAttribute('aria-expanded', 'false');
    opener?.focus({preventScroll:true});
  });
})();
