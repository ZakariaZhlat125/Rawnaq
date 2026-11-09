/* Shared by the Vue shell and direct mobile entry. Only the top window installs. */
(function () {
  'use strict';
  const build = '__PWA_BUILD__';
  if (build.startsWith('__') || window.top !== window || !window.isSecureContext || !('serviceWorker' in navigator)) return;
  const mobile = matchMedia('(max-width: 640px)');
  const standalone = matchMedia('(display-mode: standalone)');
  const isInstalled = () => standalone.matches || navigator.standalone === true;
  const ios = /iPad|iPhone|iPod/.test(navigator.userAgent) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);
  let installPrompt = null, banner = null, dismissed = false, showingHelp = false;
  try { dismissed = sessionStorage.getItem('samiPwaInstallDismissed') === '1'; } catch {}
  const copy = {
    ar: { title: 'رونق على هاتفك', install: 'تثبيت التطبيق', close: 'إغلاق', help: 'من قائمة المشاركة في Safari، اختر «إضافة إلى الشاشة الرئيسية»، ثم «إضافة».' },
    en: { title: 'Rawnaq on your phone', install: 'Install app', close: 'Close', help: 'In Safari, open the Share menu, choose “Add to Home Screen”, then “Add”.' }
  };
  function dismiss() {
    dismissed = true;
    try { sessionStorage.setItem('samiPwaInstallDismissed', '1'); } catch {}
    render();
  }
  function render() {
    const visible = mobile.matches && !isInstalled() && !dismissed && (installPrompt || ios);
    if (!visible) { banner?.remove(); banner = null; return; }
    if (!banner) {
      banner = document.createElement('aside');
      banner.id = 'sami-pwa-install';
      banner.setAttribute('translate', 'no');
      banner.innerHTML = '<img src="/pwa/icon-192.png" alt="" width="38" height="38"><strong></strong><button type="button" class="pwa-install-action"></button><button type="button" class="pwa-install-close">×</button><p id="sami-pwa-help" hidden></p>';
      banner.querySelector('.pwa-install-close').addEventListener('click', dismiss);
      banner.querySelector('.pwa-install-action').addEventListener('click', async () => {
        if (!installPrompt) { showingHelp = !showingHelp; render(); return; }
        const prompt = installPrompt;
        installPrompt = null;
        try { await prompt.prompt(); await prompt.userChoice; dismiss(); }
        catch (error) { render(); console.warn('Rawnaq: installation prompt unavailable', error); }
      });
      document.body.append(banner);
    }
    const lang = document.documentElement.lang === 'en' ? 'en' : 'ar', text = copy[lang];
    banner.lang = lang;
    banner.dir = lang === 'en' ? 'ltr' : 'rtl';
    banner.setAttribute('aria-label', text.title);
    banner.querySelector('strong').textContent = text.title;
    const button = banner.querySelector('.pwa-install-action');
    button.textContent = text.install;
    if (ios && !installPrompt) { button.setAttribute('aria-controls', 'sami-pwa-help'); button.setAttribute('aria-expanded', String(showingHelp)); }
    banner.querySelector('.pwa-install-close').setAttribute('aria-label', text.close);
    const help = banner.querySelector('p');
    help.textContent = text.help;
    help.hidden = !showingHelp;
  }
  window.addEventListener('beforeinstallprompt', event => {
    if (!mobile.matches || isInstalled()) return;
    event.preventDefault(); installPrompt = event; render();
  });
  window.addEventListener('appinstalled', () => { installPrompt = null; dismiss(); });
  standalone.addEventListener('change', render);
  mobile.addEventListener('change', render);
  new MutationObserver(render).observe(document.documentElement, { attributes: true, attributeFilter: ['lang'] });
  async function start() {
    const style = document.createElement('style');
    style.textContent = '#sami-pwa-install{position:fixed;z-index:2147482000;inset-inline:12px;bottom:calc(94px + env(safe-area-inset-bottom,0px));margin-inline:auto;max-width:406px;display:grid;grid-template-columns:38px 1fr auto 24px;gap:9px;align-items:center;padding:12px;background:#fbfaf6;color:#221f1f;border:1px solid #dec99e;border-radius:16px;box-shadow:0 8px 30px #0003;font:13px/1.5 system-ui,sans-serif}#sami-pwa-install img{border-radius:9px}#sami-pwa-install button{font:inherit;cursor:pointer;border:0;border-radius:10px}#sami-pwa-install .pwa-install-action{background:#a6741f;color:#fff;padding:9px 11px;font-weight:700}#sami-pwa-install .pwa-install-close{background:transparent;color:#554b40;font-size:24px;padding:0}#sami-pwa-install button:focus-visible{outline:3px solid #221f1f;outline-offset:3px}#sami-pwa-install p{grid-column:1/-1;margin:0;line-height:1.8}#sami-pwa-install [hidden]{display:none}';
    document.head.append(style);
    render();
    try { await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' }); }
    catch (error) { console.warn('Rawnaq: PWA registration failed', error); }
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', start, { once: true });
  else start();
})();