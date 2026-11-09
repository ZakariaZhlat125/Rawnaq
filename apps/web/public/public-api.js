/* Shared public GET queue, including the same-origin mobile iframe. */
;(function (root) {
  if (root.SamiPublicApi) return;
  try {
    if (root.parent !== root && root.parent?.SamiPublicApi) {
      root.SamiPublicApi = root.parent.SamiPublicApi;
      return;
    }
  } catch { /* Standalone or cross-origin embedding gets its own queue. */ }
  const queue = [];
  const pending = new Map();
  let active = false;
  async function drain() {
    if (active || !queue.length) return;
    active = true;
    queue.sort((a, b) => b.priority - a.priority);
    const job = queue.shift();
    const controller = new AbortController();
    const abort = () => controller.abort(job.options.signal?.reason);
    const timer = setTimeout(() => controller.abort(), 20000);
    job.options.signal?.addEventListener('abort', abort, { once: true });
    try {
      if (job.options.signal?.aborted) abort();
      const response = await root.fetch(job.url, { ...job.options, signal: controller.signal });
      // Keep the slot until the body arrives, not just until response headers.
      await response.clone().arrayBuffer();
      job.resolve(response);
    } catch (error) { job.reject(error); }
    finally {
      clearTimeout(timer);
      job.options.signal?.removeEventListener('abort', abort);
      if (job.key) pending.delete(job.key);
      active = false;
      drain();
    }
  }
  function fetchPublic(url, options = {}) {
    const headers = new Headers(options.headers);
    const language = headers.get('frezka-localization');
    if (language) {
      headers.delete('frezka-localization');
      headers.set('Accept-Language', language);
    }
    const method = (options.method || 'GET').toUpperCase();
    if (method !== 'GET' || headers.has('Authorization')) {
      throw new Error('The public API queue only accepts unauthenticated GET requests');
    }
    const absolute = new URL(url, root.location?.href).href;
    // Independent cancellation must not cancel another caller's request.
    const key = options.signal ? null : JSON.stringify([absolute, [...headers.entries()], options.cache || 'default']);
    let promise = key && pending.get(key);
    if (!promise) {
      promise = new Promise((resolve, reject) => {
        queue.push({ url: absolute, options: { ...options, headers }, key, resolve, reject,
          priority: new URL(absolute).pathname.endsWith('/Home/all') ? 100 : 0 });
      });
      if (key) pending.set(key, promise);
      // Let requests created in the same render enqueue before choosing priority.
      Promise.resolve().then(drain);
    }
    return promise.then(response => response.clone());
  }
  root.SamiPublicApi = { fetch: fetchPublic };
})(globalThis);
