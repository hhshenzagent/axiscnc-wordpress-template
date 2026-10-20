/* global document, window, FormData, fetch, MutationObserver */
/* Private inquiry files: upload first, then let Fluent Forms submit opaque references. */
(() => {
  const ready = new WeakSet();
  function init(group) {
    if (ready.has(group)) return;
    ready.add(group);
    const form = group.closest('form'), picker = group.querySelector('input[type=file]');
    const hidden = group.querySelector('input[type=hidden]'), status = group.querySelector('.wb-file-status');
    const list = group.querySelector('.wb-file-list');
    const selection = group.querySelector('.wb-file-selection');
    const clear = document.createElement('button'); clear.type = 'button'; clear.className = 'wb-file-clear'; clear.textContent = 'Clear'; clear.hidden = true;
    let files = [], busy = false, failed = false;
    const request = async (operation, values = {}) => {
      const body = new FormData();
      body.set('action', 'wb_inquiry_upload'); body.set('operation', operation);
      body.set('nonce', group.dataset.uploadNonce); body.set('form_id', group.dataset.formId);
      for (const [key, value] of Object.entries(values)) body.set(key, value);
      const response = await fetch(group.dataset.uploadUrl, {method:'POST', body, credentials:'same-origin'});
      const result = await response.json();
      if (!result.success) throw new Error(result.data?.message || 'Upload failed. Refresh the page and try again.');
      return result.data;
    };
    function render() {
      hidden.value = files.map(file => file.token).join(',');
      selection.textContent = files.length ? `${files.length} file${files.length === 1 ? '' : 's'} selected` : 'No files selected';
      clear.hidden = !failed; clear.disabled = busy;
      list.replaceChildren();
      for (const file of files) {
        const item = document.createElement('li'), name = document.createElement('span'), button = document.createElement('button');
        name.textContent = file.name; button.type = 'button'; button.textContent = 'Remove';
        button.setAttribute('aria-label', 'Remove ' + file.name);
        button.disabled = busy;
        button.addEventListener('click', async () => {
          try { await request('remove', {token:file.token}); files = files.filter(value => value !== file); failed = false; status.textContent = ''; render(); }
          catch (error) { status.textContent = error.message; }
        });
        item.append(name, button); list.append(item);
      }
    }
    picker.addEventListener('change', async () => {
      const selected = [...picker.files]; picker.value = ''; failed = false;
      if (!selected.length) return;
      const error = files.length + selected.length > 2 ? 'Choose up to 2 files.'
        : selected.some(file => file.size > 5 * 1024 * 1024) ? 'Each file must be 5 MB or smaller.'
        : selected.some(file => !file.size || !/\.(pdf|jpe?g|png)$/i.test(file.name)) ? 'Choose a PDF, JPG or PNG file.' : '';
      if (error) { failed = true; status.textContent = error; render(); return; }
      busy = true; picker.disabled = true; render(); status.textContent = 'Uploading…';
      try {
        await request('session');
        for (const file of selected) { files.push(await request('upload', {file})); render(); }
        status.textContent = '';
      } catch (error) { failed = true; status.textContent = error.message; }
      finally { busy = false; picker.disabled = false; render(); }
    });
    // Capture before Fluent Forms' AJAX handler; never silently submit while files are uploading.
    form.addEventListener('submit', event => {
      if (busy || failed) { event.preventDefault(); event.stopImmediatePropagation(); status.textContent = busy ? 'Please wait for the upload.' : 'Choose valid files or clear attachments.'; }
    }, true);
    clear.addEventListener('click', async () => {
      if (busy) return;
      try { for (const file of files) await request('remove', {token:file.token}); files = []; failed = false; status.textContent = ''; render(); }
      catch (error) { status.textContent = error.message; }
    }); group.append(clear);
    form.addEventListener('reset', () => { files = []; failed = false; status.textContent = ''; render(); });
    if (window.jQuery) window.jQuery(form).on('fluentform_submission_success', () => { files = []; failed = false; status.textContent = ''; render(); });
    render();
  }
  function scan() { document.querySelectorAll('.wb-inquiry-files').forEach(init); }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', scan); else scan();
  new MutationObserver(scan).observe(document.documentElement, {childList:true, subtree:true});
})();

