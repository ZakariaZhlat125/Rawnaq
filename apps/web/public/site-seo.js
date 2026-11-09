/* Shared metadata updater for Vue and the standalone mobile application. */
;(function (root) {
  const names = {
    '/':['الرئيسية','Home'], '/services':['الخدمات','Services'], '/store':['المنتجات','Products'],
    '/gifts':['الإهداء','Gifts'], '/packages-gifts':['الباقات','Packages'], '/branches':['الفروع','Branches'],
    '/contact':['تواصل معنا','Contact us'], '/page-about':['عن رونق','About Rawnaq'],
    '/blog':['المدونة','Blog'], '/home-service':['الخدمة المنزلية','Home service'], '/cafe':['المقهى','Cafe'],
    '/booking':['الحجز','Booking'], '/booking-receipt':['إيصال الحجز','Booking receipt'],
    '/gift-recipient':['استلام الهدية','Receive your gift'], '/account':['حسابي','My account'],
    '/terms':['الشروط والأحكام','Terms and conditions'], '/privacy-policy':['سياسة الخصوصية','Privacy policy']
  };
  function normalize(path) {
    path = '/' + String(path || '/').split(/[?#]/)[0].replace(/^\/+|\/+$/g, '');
    const aliases = {'/index.html':'/', '/about.html':'/page-about', '/TermsAndConditions':'/terms', '/privacy':'/privacy-policy', '/packages':'/packages-gifts'};
    return aliases[path] || path.replace(/\.html$/, '');
  }
  function supported(path) {
    return Object.hasOwn(names, path) || /^\/services\/\d+$/.test(path) || /^\/blog\/[^/]+$/.test(path) || /^\/(packages|offers)\/\d+$/.test(path);
  }
  function fallback(path, lang) {
    const key = names[path] ? path : path.startsWith('/services/') ? '/services' : path.startsWith('/blog/') ? '/blog' : /^\/(packages|offers)\//.test(path) ? '/packages-gifts' : '/';
    const english = lang === 'en';
    return {
      keywords: '',
      title: names[key][english ? 1 : 0] + (english ? ' | Rawnaq' : ' | رونق'),
      description: english ? 'Explore Rawnaq services, packages and men’s grooming in Jeddah.' : 'اكتشف خدمات وباقات العناية الرجالية من رونق في جدة.'
    };
  }
  function apply(doc, data) {
    setKeywords(doc, data.keywords);
    let title = doc.head.querySelector('title');
    if (!title) { title = doc.createElement('title'); doc.head.append(title); }
    title.setAttribute('translate', 'no');
    title.textContent = data.title;
    doc.head.querySelectorAll('title').forEach(node => { if (node !== title) node.remove(); });
    let description = doc.head.querySelector('meta[name="description"]');
    if (!description) { description = doc.createElement('meta'); description.name = 'description'; doc.head.append(description); }
    description.content = data.description;
    doc.head.querySelectorAll('meta[name="description"]').forEach(node => { if (node !== description) node.remove(); });
  }
  function meta(doc, attribute, key, content) {
    const nodes = [...doc.head.querySelectorAll(`meta[${attribute}="${key}"]`)];
    const node = nodes.shift() || doc.createElement('meta');
    node.setAttribute(attribute, key); node.content = content;
    if (!node.parentNode) doc.head.append(node);
    nodes.forEach(duplicate => duplicate.remove());
  }
  function technical(doc, path, lang, data, missing = false) {
    const url = new URL(path, new URL(doc.baseURI).origin).href;
    const nodes = [...doc.head.querySelectorAll('link[rel="canonical"]')];
    const canonical = nodes.shift() || doc.createElement('link');
    canonical.rel = 'canonical'; canonical.href = url;
    if (!canonical.parentNode) doc.head.append(canonical);
    nodes.forEach(duplicate => duplicate.remove());
    const privatePage = ['/booking','/booking-receipt','/gift-recipient','/account'].includes(path);
    meta(doc, 'name', 'robots', missing || privatePage || !supported(path) ? 'noindex,follow' : 'index,follow');
    meta(doc, 'property', 'og:url', url);
    meta(doc, 'property', 'og:type', path.startsWith('/blog/') ? 'article' : 'website');
    meta(doc, 'property', 'og:locale', lang === 'en' ? 'en_US' : 'ar_SA');
    meta(doc, 'property', 'og:title', data.title);
    meta(doc, 'property', 'og:description', data.description);
    meta(doc, 'name', 'twitter:card', 'summary');
    meta(doc, 'name', 'twitter:title', data.title);
    meta(doc, 'name', 'twitter:description', data.description);
  }
  function setKeywords(doc, value) {
    const nodes = [...doc.head.querySelectorAll('meta[name="keywords"]')];
    const content = typeof value === 'string' ? value.trim() : '';
    if (!content) { nodes.forEach(node => node.remove()); return; }
    const node = nodes.shift() || doc.createElement('meta');
    node.name = 'keywords';
    node.content = content;
    if (!node.parentNode) doc.head.append(node);
    nodes.forEach(duplicate => duplicate.remove());
  }
  function create({ endpoint, doc = document, fetcher = (...args) => (root.SamiPublicApi?.fetch || fetch)(...args) }) {
    let serial = 0, lastKey = '', controller;
    async function update(path, lang, force = false) {
      path = normalize(path); lang = lang === 'en' ? 'en' : 'ar';
      if (!supported(path)) { serial++; controller?.abort(); const data = fallback(path, lang); data.title = lang === 'en' ? 'Page not found | Rawnaq' : 'الصفحة غير موجودة | رونق'; apply(doc, data); technical(doc, path, lang, data, true); lastKey = ''; return; }
      const key = path + ':' + lang;
      if (!force && key === lastKey) return;
      lastKey = key;
      const request = ++serial;
      controller?.abort();
      controller = new AbortController();
      const current = controller;
      const timer = setTimeout(() => current.abort(), 8000);
      const defaults = fallback(path, lang);
      apply(doc, defaults);
      technical(doc, path, lang, defaults);
      try {
        const url = new URL(endpoint, doc.baseURI);
        url.searchParams.set('path', path); url.searchParams.set('lang', lang);
        const response = await fetcher(url.href, { signal: current.signal, cache: 'no-store', headers: { Accept: 'application/json' } });
        if (!response.ok) { if (response.status === 404 && request === serial) technical(doc, path, lang, defaults, true); throw new Error('SEO unavailable'); }
        const body = await response.json();
        if (request !== serial || !body.status || !body.data) return;
        const data = body.data;
        const resolved = {
          keywords: typeof data.keywords === 'string' ? data.keywords : '',
          title: typeof data.title === 'string' && data.title.trim() ? data.title : defaults.title,
          description: typeof data.description === 'string' && data.description.trim() ? data.description : defaults.description
        };
        apply(doc, resolved);
        technical(doc, path, lang, resolved);
      } catch { /* Keep localized defaults when offline or the API is unavailable. */ }
      finally { clearTimeout(timer); }
    }
    return { update, dispose() { serial++; controller?.abort(); } };
  }
  root.SamiSeo = { create, normalize, supported, fallback, apply };
})(globalThis);
