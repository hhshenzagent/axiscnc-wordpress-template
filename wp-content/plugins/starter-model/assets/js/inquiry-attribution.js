/* global window, document, MutationObserver, TextEncoder, btoa, URL */
/**
 * First-party inquiry attribution. No network calls, cookies or third-party dependencies.
 * The factory is exposed so the same acquisition rules run in browser and regression tests.
 */
(function (root) {
  'use strict';
  const KEY = 'axiscnc_template_attribution_v1';
  const URL_LIMIT = 16384;
  const PAYLOAD_LIMIT = 131072;
  const DAY = 86400000;
  const CAMPAIGN_KEYS = new Set(['gclid', 'gbraid', 'wbraid', 'msclkid', 'gad_source', 'gad_campaignid']);
  const SECRET_KEYS = new Set(['access_token', 'id_token', 'auth_token', 'authorization', 'password', 'client_secret']);
  const LABEL_KEYS = [
    'wb_first_landing_url', 'wb_first_referrer', 'wb_first_touch_at',
    'wb_last_landing_url', 'wb_last_referrer', 'wb_last_touch_at',
    'wb_submission_url', 'wb_attribution_status',
  ];
  const FLAGS = new Set(['redacted', 'url_too_long', 'invalid_url', 'first_missing']);
  const byteLength = value => new TextEncoder().encode(value).length;

  function inspectUrl(raw) {
    if (!raw) return {url: '', flags: []};
    if (typeof raw !== 'string') return {url: '', flags: ['invalid_url']};
    if (byteLength(raw) > URL_LIMIT) return {url: '', flags: ['url_too_long']};
    if (/[<>"\\]/.test(raw) || [...raw].some(character => character.charCodeAt(0) <= 32 || character.charCodeAt(0) === 127)) return {url: '', flags: ['invalid_url']};
    let parsed;
    try { parsed = new URL(raw); } catch { return {url: '', flags: ['invalid_url']}; }
    if (!['http:', 'https:'].includes(parsed.protocol) || parsed.username || parsed.password) return {url: '', flags: ['invalid_url']};
    let redacted = false;
    // Replace only secret parameter values; leave every other byte, order and duplicate key intact.
    const replaceSecrets = part => part.split('&').map(item => {
      const separator = item.indexOf('=');
      const rawKey = separator < 0 ? item : item.slice(0, separator);
      let key;
      try { key = decodeURIComponent(rawKey.replace(/\+/g, ' ')).toLowerCase(); } catch { return item; }
      if (!SECRET_KEYS.has(key)) return item;
      redacted = true;
      return rawKey + '=REDACTED';
    }).join('&');
    const hashIndex = raw.indexOf('#');
    let main = hashIndex < 0 ? raw : raw.slice(0, hashIndex);
    let hash = hashIndex < 0 ? '' : raw.slice(hashIndex + 1);
    const queryIndex = main.indexOf('?');
    if (queryIndex >= 0) main = main.slice(0, queryIndex + 1) + replaceSecrets(main.slice(queryIndex + 1));
    if (hash.includes('=')) hash = replaceSecrets(hash);
    return {url: main + (hashIndex < 0 ? '' : '#' + hash), flags: redacted ? ['redacted'] : []};
  }

  function campaignSignature(url) {
    if (!url) return '';
    const parameters = [...new URL(url).searchParams.entries()]
      .filter(([key, value]) => value && (key.toLowerCase().startsWith('utm_') || CAMPAIGN_KEYS.has(key.toLowerCase())))
      .map(([key, value]) => [key.toLowerCase(), value])
      .sort((a, b) => a[0].localeCompare(b[0]) || a[1].localeCompare(b[1]));
    return parameters.length ? JSON.stringify(parameters) : '';
  }

  function createTracker(environment, configuration = {}) {
    const retention = Math.min(365, Math.max(1, Number(configuration.retentionDays) || 90)) * DAY;
    const hosts = new Set((configuration.internalHosts || [new URL(environment.url()).hostname]).map(host => String(host).toLowerCase()));
    const storageKey = configuration.storageKey || KEY;
    let allowed = configuration.storageAllowed !== false;
    let memory = null;
    let mode = 'page_only';
    const emptyState = () => ({version: 1, first: null, last: null, flags: [], updatedAt: environment.now()});
    const isInternal = url => url && hosts.has(new URL(url).hostname.toLowerCase());

    function validateTouch(touch, now) {
      if (!touch || typeof touch !== 'object' || typeof touch.at !== 'string') return null;
      const at = Date.parse(touch.at);
      if (!Number.isFinite(at) || at > now + 300000 || at + retention <= now) return null;
      const url = inspectUrl(touch.url);
      const referrer = inspectUrl(touch.referrer);
      if (!url.url || !isInternal(url.url) || url.flags.length && !url.flags.includes('redacted') || referrer.flags.some(flag => flag !== 'redacted')) return null;
      return {url: url.url, referrer: isInternal(referrer.url) ? '' : referrer.url, at: touch.at};
    }

    function parseState(raw) {
      if (!raw) return null;
      try {
        const state = JSON.parse(raw);
        if (!state || state.version !== 1 || !Number.isFinite(state.updatedAt) || state.updatedAt > environment.now() + 300000 || !Array.isArray(state.flags)) return null;
        const first = validateTouch(state.first, environment.now());
        const last = validateTouch(state.last, environment.now());
        if (!first && !last) return null;
        return {version: 1, first, last, flags: state.flags.filter(flag => FLAGS.has(flag)), updatedAt: state.updatedAt};
      } catch { return null; }
    }

    function storageCandidates() {
      const stores = [];
      for (const [getStore, storageMode] of [[environment.local, 'captured'], [environment.session, 'session_only']]) {
        try {
          const storage = getStore?.();
          if (storage) stores.push({storage, mode: storageMode});
        } catch { /* Browser policy may deny access even to the storage property. */ }
      }
      return stores;
    }

    function load() {
      const candidates = [];
      // A readable store can remain stale when both writes fail. Keep the newest
      // valid page record as a candidate, with its actual persistence status.
      const pageState = memory && parseState(JSON.stringify(memory));
      if (pageState) candidates.push({state: pageState, mode});
      for (const candidate of storageCandidates()) {
        try {
          const state = parseState(candidate.storage.getItem(storageKey));
          if (state) candidates.push({...candidate, state});
        } catch { /* Keep the inquiry usable when reading storage is denied. */ }
      }
      candidates.sort((a, b) => b.state.updatedAt - a.state.updatedAt);
      if (candidates.length) {
        memory = candidates[0].state;
        mode = candidates[0].mode;
      } else if (memory) memory = parseState(JSON.stringify(memory));
      return memory || emptyState();
    }

    function persist(state) {
      memory = state;
      mode = 'page_only';
      for (const candidate of storageCandidates()) {
        try {
          candidate.storage.setItem(storageKey, JSON.stringify(state));
          mode = candidate.mode;
          // Once local persistence works, discard only our former session fallback.
          if (mode === 'captured') {
            try { environment.session?.()?.removeItem(storageKey); } catch { /* Optional cleanup. */ }
          }
          return;
        } catch { /* Quota/policy errors fall through to the next storage. */ }
      }
    }

    function capture() {
      if (!allowed) return;
      const state = load();
      const current = inspectUrl(environment.url());
      const suppliedReferrer = inspectUrl(environment.referrer());
      const internalReferrer = isInternal(suppliedReferrer.url);
      const referrer = internalReferrer ? '' : suppliedReferrer.url;
      const signature = campaignSignature(current.url);
      const now = environment.now();
      const initialArrival = !state.first && !internalReferrer;
      if (current.url && isInternal(current.url)) {
        const touch = {url: current.url, referrer, at: new Date(now).toISOString()};
        if (initialArrival) state.first = touch;
        const oldSignature = campaignSignature(state.last?.url);
        const repeatedArrival = state.last && state.last.url === current.url && state.last.referrer === referrer
          && now - Date.parse(state.last.at) < 1800000;
        const newCampaign = signature && (signature !== oldSignature || !internalReferrer && !repeatedArrival);
        if (initialArrival && !state.last || newCampaign || referrer && !repeatedArrival) state.last = touch;
      }
      state.flags = [...new Set([...state.flags, ...current.flags, ...suppliedReferrer.flags])];
      state.updatedAt = now;
      persist(state);
    }

    function read() {
      const fields = Object.fromEntries(LABEL_KEYS.map(key => [key, '']));
      if (!allowed) { fields.wb_attribution_status = 'consent_denied'; return fields; }
      const state = load();
      const submission = inspectUrl(environment.url());
      for (const [prefix, touch] of [['first', state.first], ['last', state.last]]) {
        if (!touch) continue;
        fields['wb_' + prefix + '_landing_url'] = touch.url;
        fields['wb_' + prefix + '_referrer'] = touch.referrer;
        fields['wb_' + prefix + '_touch_at'] = touch.at;
      }
      fields.wb_submission_url = submission.url;
      const flags = [...new Set([...state.flags, ...submission.flags, ...(!state.first ? ['first_missing'] : [])])];
      fields.wb_attribution_status = [mode, ...flags].join('|');
      return fields;
    }

    function setStorageAllowed(value) {
      allowed = value === true;
      if (!allowed) {
        for (const candidate of storageCandidates()) {
          try { candidate.storage.removeItem(storageKey); } catch { /* No other keys are touched. */ }
        }
        memory = null;
        mode = 'page_only';
      } else capture();
    }

    return {capture, read, setStorageAllowed};
  }

  function encodePayload(data) {
    const bytes = new TextEncoder().encode(JSON.stringify({version: 1, data}));
    let binary = '';
    for (let start = 0; start < bytes.length; start += 4096) binary += String.fromCharCode(...bytes.subarray(start, start + 4096));
    const result = btoa(binary);
    return result.length <= PAYLOAD_LIMIT ? result : '';
  }

  const api = {createTracker, inspectUrl, encodePayload, storageKey: KEY, fieldNames: LABEL_KEYS};
  root.AxisTemplateAttribution = api;
  if (typeof window === 'undefined' || typeof document === 'undefined' || !window.axisTemplateAttributionConfig) return;
  const tracker = createTracker({
    url: () => window.location.href,
    referrer: () => document.referrer,
    now: () => Date.now(),
    local: () => window.localStorage,
    session: () => window.sessionStorage,
  }, window.axisTemplateAttributionConfig);
  tracker.capture();

  function fillForm(form) {
    const transport = form.querySelector('input[type="hidden"][name="wb_attribution_payload"]');
    if (!transport) return;
    const fields = tracker.read();
    for (const [name, value] of Object.entries(fields)) {
      const input = form.querySelector('input[type="hidden"][name="' + name + '"]');
      if (input) input.value = value;
    }
    transport.value = encodePayload(fields);
  }
  function fillAll() {
    for (const form of document.querySelectorAll('form[id^="fluentform_"]')) fillForm(form);
  }
  document.addEventListener('submit', event => {
    if (event.target?.matches('form[id^="fluentform_"]')) fillForm(event.target);
  }, true);
  document.addEventListener('click', event => {
    const button = event.target?.closest('button, input[type="submit"], input[type="image"]');
    const form = button?.closest('form[id^="fluentform_"]');
    if (form) fillForm(form);
  }, true);
  function ready() {
    fillAll();
    new MutationObserver(mutations => {
      if (mutations.some(mutation => mutation.addedNodes.length)) fillAll();
    }).observe(document.body, {childList: true, subtree: true});
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', ready, {once: true});
  else ready();
  window.addEventListener('storage', event => { if (event.key === KEY) fillAll(); });
  window.addEventListener('axiscnc-template:attribution-consent', event => {
    tracker.setStorageAllowed(event.detail?.granted === true);
    fillAll();
  });
  api.getData = () => tracker.read();
  api.refresh = fillAll;
  api.setStorageAllowed = value => { tracker.setStorageAllowed(value); fillAll(); };
})(globalThis);

