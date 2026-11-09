/**
 * File: public/custom-js/cart-sidebar.js
 *
 * Route needed (web.php):
 *   Route::get('/cart/sidebar-data', [CartController::class, 'sidebarData'])->name('cart.sidebar');
 */

(function () {
  'use strict';

  const cfg = Object.assign({
    fetchUrl:   '/cart/sidebar-data',
    cartPageUrl:'/cart',
    updateQtyUrl:'/cart/product',
    currency:   'ر.س',
    lang:       document.documentElement.lang || 'ar',
    csrfToken:  document.querySelector('meta[name="csrf-token"]')?.content || '',
  }, window.CartSidebarConfig || {});

  const t  = (ar, en) => cfg.lang === 'ar' ? ar : en;
  const $  = id => document.getElementById(id);

  const panel     = () => $('cartSidebarPanel');
  const overlay   = () => $('cartOverlay');
  const badge     = () => $('cartTriggerBadge');
  const loadingEl = () => $('cartLoadingState');
  const emptyEl   = () => $('cartEmptyState');
  const listEl    = () => $('cartProductsList');
  const itemsCont = () => $('cartItemsContainer');
  const template  = () => $('cartItemTemplate');

  let loaded = false;

  /* ── Open / Close ── */
  window.openCartSidebar = function () {
    panel()?.classList.add('open');
    overlay()?.classList.add('show');
    document.body.style.overflow = 'hidden';
    if (!loaded) fetchCartData();
  };

  window.closeCartSidebar = function () {
    panel()?.classList.remove('open');
    overlay()?.classList.remove('show');
    document.body.style.overflow = '';
  };

  document.addEventListener('DOMContentLoaded', () => {
    $('cartTriggerBtn')?.addEventListener('click', window.openCartSidebar);
    document.addEventListener('keydown', e => { if (e.key === 'Escape') window.closeCartSidebar(); });

    // Event delegation for remove buttons (cloned from template)
    document.getElementById('cartItemsContainer')?.addEventListener('click', function (e) {
      const btn = e.target.closest('.csp-item-remove');
      if (btn) removeSidebarItem(btn);
    });

    // Event delegation for qty controls
    document.getElementById('cartItemsContainer')?.addEventListener('click', function (e) {
      const btn = e.target.closest('.csp-qty-btn');
      if (!btn) return;

      const itemEl = btn.closest('.csp-item');
      if (!itemEl || itemEl.dataset.itemType !== 'product') return;

      const currentQty = parseInt(itemEl.dataset.qty || '1', 10);
      const action = btn.dataset.action;
      const nextQty = action === 'increase' ? currentQty + 1 : currentQty - 1;

      updateProductQty(itemEl, nextQty);
    });
  });

  /* ── Fetch ── */
  function fetchCartData() {
    showState('loading');
    fetch(cfg.fetchUrl, {
      headers: {
        'Accept':           'application/json',
        'X-CSRF-TOKEN':     cfg.csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      }
    })
      .then(r => r.json())
      .then(data => { loaded = true; renderCart(data); })
      .catch(() => showState('empty'));
  }

  /* ── Render ── */
  function renderCart(data) {
    const container = itemsCont();
    container.innerHTML = '';

    const products = data.products || [];
    const services = data.services || [];
    const gifts    = data.gifts    || [];
    const packages = data.packages || [];
    const total    = products.length + services.length + gifts.length + packages.length;

    if (total === 0) { showState('empty'); updateBadge(0); return; }

    if (products.length) {
      container.appendChild(sectionHeading('bi-box-seam', t('المنتجات', 'Products')));
      products.forEach(item => container.appendChild(buildItem(item)));
    }

    if (services.length) {
      container.appendChild(sectionHeading('bi-wrench-adjustable', t('الخدمات', 'Services')));
      services.forEach(item => container.appendChild(buildItem(item)));
    }

    if (gifts.length) {
      container.appendChild(sectionHeading('bi-gift', t('بطاقات الهدايا', 'Gift Cards')));
      gifts.forEach(item => container.appendChild(buildItem(item)));
    }

    if (packages.length) {
      container.appendChild(sectionHeading('bi-box2', t('الباقات', 'Packages')));
      packages.forEach(item => container.appendChild(buildItem(item)));
    }

    /* Summary */
    const s   = data.summary || {};
    const cur = s.currency || cfg.currency;

    setText('cspProductsCount', (s.products_count ?? 0) + ' ' + t('منتج',  'item(s)'));
    setText('cspServicesCount', (s.services_count ?? 0) + ' ' + t('خدمة',  'service(s)'));
    setText('cspGiftsCount',    (s.gifts_count    ?? 0) + ' ' + t('بطاقة', 'card(s)'));
    setText('cspPackagesCount', (s.packages_count ?? 0) + ' ' + t('باقة',  'package(s)'));
    setText('cspSubtotal',   formatPrice(s.subtotal,  cur));
    setText('cspDiscount',   parseFloat(s.discount || 0) > 0
      ? '- ' + formatPrice(s.discount, cur)
      : t('لا يوجد', 'None'));
    setText('cspFinalPrice', formatPrice(s.final, cur));

    updateBadge(total);
    showState('list');
  }

  function sectionHeading(icon, label) {
    const d = document.createElement('div');
    d.className = 'csp-section-title mt-3';
    d.innerHTML = `<i class="bi ${icon} me-1"></i>${label}`;
    return d;
  }

  function buildItem(item) {
    const clone = template().content.cloneNode(true);
    const el    = clone.querySelector('.csp-item');

    el.dataset.itemId   = item.id;
    el.dataset.itemType = item.type;
    el.dataset.qty      = item.qty ?? 1;

    /* Thumb */
    const thumb = el.querySelector('.csp-item-thumb');
    if (item.image) {
      const img = document.createElement('img');
      img.src = item.image; img.alt = item.name; img.loading = 'lazy';
      thumb.appendChild(img);
    } else {
      const icon = document.createElement('i');
      icon.className = 'bi bi-person';
      thumb.appendChild(icon);
    }

    el.querySelector('.csp-item-name').textContent = item.name;

    /* Prices */
    const orig = el.querySelector('.csp-item-original');
    if (item.original_price && String(item.original_price) !== String(item.final_price)) {
      orig.textContent = formatPrice(item.original_price, item.currency || cfg.currency);
    } else {
      orig.style.display = 'none';
    }
    el.querySelector('.csp-item-final').textContent = formatPrice(item.final_price, item.currency || cfg.currency);

    /* Discount badge */
    const disc = el.querySelector('.csp-item-discount');
    if (disc) {
      if (parseFloat(item.discount_amount || 0) > 0) {
        disc.textContent = '- ' + formatPrice(item.discount_amount, item.currency || cfg.currency);
      } else {
        disc.style.display = 'none';
      }
    }

    /* Coupon badge */
    const coupon = el.querySelector('.csp-item-coupon');
    if (coupon) {
      if (item.coupon_code) {
        coupon.innerHTML = `<i class="bi bi-tag-fill me-1"></i>${item.coupon_code}`;
      } else {
        coupon.style.display = 'none';
      }
    }

    /* Meta line: qty · employee · booking date */
    const meta = el.querySelector('.csp-item-meta');
    if (meta) {
      const parts = [];
      if (item.qty           && item.qty  > 1) parts.push(t('الكمية: ',  'Qty: ')   + item.qty);
      if (item.employee_name)                  parts.push(t('الموظف: ',  'Staff: ')  + item.employee_name);
      if (item.booking_date)                   parts.push(t('الموعد: ',  'Date: ')   + item.booking_date);
      meta.textContent   = parts.join('  •  ');
      meta.style.display = parts.length ? '' : 'none';
    }

    /* Qty controls (products only) */
    const qtyWrap = el.querySelector('.csp-item-qty');
    if (qtyWrap) {
      if (item.type === 'product') {
        const qtyVal = qtyWrap.querySelector('.csp-qty-val');
        if (qtyVal) qtyVal.textContent = item.qty ?? 1;
        qtyWrap.style.display = 'flex';

        const decBtn = qtyWrap.querySelector('[data-action="decrease"]');
        const incBtn = qtyWrap.querySelector('[data-action="increase"]');
        if (decBtn) decBtn.setAttribute('aria-label', t('ØªÙ‚Ù„ÙŠÙ„ Ø§Ù„ÙƒÙ…ÙŠØ©', 'Decrease quantity'));
        if (incBtn) incBtn.setAttribute('aria-label', t('Ø²ÙŠØ§Ø¯Ø© Ø§Ù„ÙƒÙ…ÙŠØ©', 'Increase quantity'));
      } else {
        qtyWrap.style.display = 'none';
      }
    }

    return el;
  }

  /* ── Remove Item ── */
  function updateProductQty(itemEl, nextQty) {
    const id = itemEl?.dataset?.itemId;
    if (!id) return;

    const url = `${cfg.updateQtyUrl}/${id}/qty`;
    const btns = itemEl.querySelectorAll('.csp-qty-btn');
    btns.forEach(b => b.disabled = true);

    fetch(url, {
      method: 'PATCH',
      headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': cfg.csrfToken,
        'X-Requested-With': 'XMLHttpRequest',
      },
      body: JSON.stringify({ qty: nextQty }),
    })
      .then(r => r.json().then(data => ({ ok: r.ok, data })))
      .then(({ ok }) => {
        if (!ok) throw new Error('qty-update-failed');
        loaded = false;
        fetchCartData();
      })
      .catch(() => {
        btns.forEach(b => b.disabled = false);
      });
  }

  function removeSidebarItem(btn) {
    const itemEl = btn.closest('.csp-item');
    const id     = itemEl?.dataset.itemId;
    const type   = itemEl?.dataset.itemType;   // 'product' | 'service' | 'gift'
    if (!id) return;

    // Adjust these URLs to match your actual DELETE routes
    const urls = {
      product: `/cart/product/${id}`,
      service: `/cart/service/${id}`,
      gift:    `/cart/gift/${id}`,
    };

    itemEl.classList.add('removing');

    setTimeout(() => {
      fetch(urls[type] || `/cart/item/${id}`, {
        method:  'DELETE',
        headers: {
          'Accept':       'application/json',
          'X-CSRF-TOKEN': cfg.csrfToken,
        },
      })
        .then(r => r.json())
        .then(() => { itemEl.remove(); loaded = false; fetchCartData(); })
        .catch(() => itemEl.classList.remove('removing'));
    }, 300);
  }

  /* ── Helpers ── */
  function showState(state) {
    loadingEl().style.display = state === 'loading' ? 'block' : 'none';
    emptyEl().style.display   = state === 'empty'   ? 'flex'  : 'none';
    listEl().style.display    = state === 'list'    ? 'block' : 'none';
  }
  function updateBadge(n) {
    const el = badge();
    if (!el) return;
    el.textContent = n;
    el.classList.toggle('csp-badge-zero', n == 0);
    // pop animation on change
    el.classList.remove('csp-badge-pop');
    void el.offsetWidth; // reflow to restart animation
    if (n > 0) el.classList.add('csp-badge-pop');
  }
  function setText(id, v) { const el = $(id); if (el) el.textContent = v; }
  function formatPrice(val, currency) {
    if (val === null || val === undefined || val === '') return '—';
    const n = parseFloat(val);
    if (isNaN(n)) return val;
    return n.toLocaleString('ar-SA', { minimumFractionDigits: 0, maximumFractionDigits: 2 })
      + ' ' + (currency || cfg.currency);
  }

  /* ── Public API ── */
  window.CartSidebar = {
    open:     window.openCartSidebar,
    close:    window.closeCartSidebar,
    refresh:  () => { loaded = false; fetchCartData(); },
    setBadge: updateBadge,
  };

})();

/* ================================================================
   Auto-hook: wraps the global addtocart() function so the sidebar
   opens automatically whenever a product is added — no changes
   needed in your existing addtocart code.
   Works ONLY if addtocart is defined BEFORE this script runs,
   so place cart-sidebar.js last in @stack("after-scripts").
   If addtocart is defined AFTER, use the manual approach instead
   (see addtocart_update.js).
================================================================ */
document.addEventListener("DOMContentLoaded", function () {
  if (typeof window.addtocart === "function") {
    const _original = window.addtocart;
    window.addtocart = function (product_id) {
      // Call the original — but intercept the AJAX success
      // by temporarily wrapping $.ajax if jQuery is present
      if (window.jQuery) {
        const _ajax = jQuery.ajax.bind(jQuery);
        jQuery.ajax = function (options) {
          const _success = options.success;
          options.success = function (response) {
            if (_success) _success.apply(this, arguments);
            CartSidebar.refresh();
            CartSidebar.open();
          };
          jQuery.ajax = _ajax; // restore immediately
          return _ajax(options);
        };
      }
      return _original(product_id);
    };
  }
});
