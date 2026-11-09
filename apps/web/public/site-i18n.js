/* Shared DOM translator. Only known UI strings are translated; user input is untouched. */
;(function(root) {
  const attrs = ['placeholder', 'title', 'aria-label', 'alt'];
  const normalize = value => value.replace(/\s+/g, ' ').trim();
  function translate(value, lang) {
    if (lang !== 'en' || typeof value !== 'string') return value;
    const key = normalize(value);
    const dict = root.SamiDictionary;
    let result = Object.prototype.hasOwnProperty.call(dict, key) ? dict[key] : undefined;
    if (result === undefined) {
      const quantity = key.match(/^([\d٠-٩.,٬٫]+)\s*(ر\.س|ريال|دقيقة|دقائق|خدمة|خدمات|نقطة|نقاط|منتج|منتجات|تقييم|تقييمات|أنواع|باقات متاحة)$/);
      if (quantity) result = quantity[1] + ' ' + ({'ر.س':'SAR','ريال':'SAR','دقيقة':'min','دقائق':'min','خدمة':'services','خدمات':'services','نقطة':'points','نقاط':'points','منتج':'products','منتجات':'products','تقييم':'reviews','تقييمات':'reviews','أنواع':'types','باقات متاحة':'packages available'})[quantity[2]];
    }
    if (result === undefined) {
      // Anchored UI templates preserve live counts without translating arbitrary prose.
      const patterns = [
        [/^عرض (\d+) - (\d+) من (\d+) منتج$/, (_, first, last, total) => 'Showing ' + first + '–' + last + ' of ' + total + ' products'],
        [/^عرض جميع المنتجات \((\d+)\)$/, (_, count) => 'View all products (' + count + ')'],
        [/^الكمية: (\d+)$/, (_, count) => 'Quantity: ' + count],
        [/^منتجاتك \((\d+)\)$/, (_, count) => 'Your products (' + count + ')'],
        [/^(\d+) خدمات متكاملة بأعلى معايير الجودة والخصوصية\.$/, (_, count) => count + ' services with the highest standards of quality and privacy.'],
        [/^اختر الوقت المناسب لك — مدة الباقة (\d+) دقيقة$/, (_, minutes) => 'Choose a convenient time — package duration: ' + minutes + ' min'],
        [/^ادفع الآن — ([\d.,]+) ر\.س$/, (_, amount) => 'Pay now — ' + amount + ' SAR'],

        [/^الخطوة (\d+) من (\d+)$/, (_, step, total) => 'Step ' + step + ' of ' + total],
        [/^يبدأ من ([\d.,]+) ر\.س$/, (_, amount) => 'From ' + amount + ' SAR'],
        [/^\((\d+) تقييم\)$/, (_, count) => '(' + count + ' reviews)'],
        [/^([^\p{L}\p{N}]*)(\d+) خيارات$/u, (_, icon, count) => icon + count + ' options'],
        [/^المتوفر بالمخزون: (\d+)$/, (_, count) => 'In stock: ' + count],
        [/^فتح السلة، (\d+) منتجات$/, (_, count) => 'Open cart, ' + count + ' products'],
        [/^الهدايا اللي أرسلتها \((\d+)\)$/, (_, count) => 'Gifts I sent (' + count + ')'],
        [/^([\d.,]+) ر\.س رصيد$/, (_, amount) => amount + ' SAR balance'],
        // Gifts (AccountGifts.vue, GiftValueStep.vue)
        [/^بطاقة هدية بقيمة ([\d.,]+) ر\.س$/, (_, amount) => 'Gift card worth ' + amount + ' SAR'],
        [/^هداياي المستلمة \((\d+)\)$/, (_, n) => 'Received gifts (' + n + ')'],
        [/^هداياي المرسلة \((\d+)\)$/, (_, n) => 'Sent gifts (' + n + ')'],
        [/^المبلغ بين ([\d.,]+) و ([\d.,]+) ر\.س · صالحة لمدة (\d+) أشهر$/, (_, min, max, m) => 'Amount between ' + min + ' and ' + max + ' SAR · valid for ' + m + ' months'],
        // Wallet (AccountWallet.vue)
        [/^ينتهي ([\d.,]+) ر\.س من الرصيد الترويجي بتاريخ ([\d-]+)$/, (_, amount, date) => amount + ' SAR of promotional balance expires on ' + date],
        [/^🎁 ستحصل على ([\d.,]+) ر\.س رصيداً ترويجياً مع هذا الشحن$/, (_, amount) => '🎁 You will get ' + amount + ' SAR promotional balance with this top-up'],
        [/^اشحن ([\d.,]+) ر\.س واحصل على ([\d.,]+) ر\.س$/, (_, min, bonus) => 'Top up ' + min + ' SAR, get ' + bonus + ' SAR'],
        [/^صلاحية المكافأة (\d+) يوم$/, (_, days) => 'Bonus valid for ' + days + ' days'],
        [/^يُضاف الرصيد بعد تأكيد الدفع من البوابة\. الحد: ([\d.,]+) – ([\d.,]+) ر\.س$/, (_, min, max) => 'Balance is added once the payment gateway confirms the payment. Limits: ' + min + ' – ' + max + ' SAR'],
        [/^لديك ([\d.,]+) نقطة · النقطة = ([\d.,]+) ر\.س · الحد الأدنى ([\d.,]+) نقطة$/, (_, points, value, min) => 'You have ' + points + ' points · 1 point = ' + value + ' SAR · minimum ' + min + ' points'],
        [/^= ([\d.,]+) ر\.س رصيد ترويجي$/, (_, amount) => '= ' + amount + ' SAR promotional balance'],
        [/^ينتهي ([\d-]+)$/, (_, date) => 'Expires ' + date],
        [/^الرصيد ([\d.,]+)$/, (_, amount) => 'Balance ' + amount],
        [/^يُحوَّل إلى حسابك البنكي بعد المراجعة\. المتاح للسحب: ([\d.,]+) ر\.س · الحد الأدنى ([\d.,]+) ر\.س$/, (_, available, min) => 'Transferred to your bank account after review. Available: ' + available + ' SAR · minimum ' + min + ' SAR'],
        [/^عند إلغاء حجز مدفوع قبل موعده بـ ([\d.,]+) ساعة أو أكثر يُرجع المبلغ كاملاً إلى محفظتك، وبعد ذلك تُخصم رسوم ([\d.,]+)%\.$/, (_, hours, fee) => 'Cancel a paid booking at least ' + hours + ' hours before it starts and the full amount returns to your wallet; later cancellations carry a ' + fee + '% fee.'],
        [/^مبلغ الشحن يجب أن يكون بين ([\d.,]+) و ([\d.,]+) ر\.س$/, (_, min, max) => 'Top-up amount must be between ' + min + ' and ' + max + ' SAR'],
      ];
      for (const [pattern, render] of patterns) {
        if (pattern.test(key)) { result = key.replace(pattern, render); break; }
      }
    }
    return result === undefined ? value : value.replace(value.trim(), result);
  }
  function createTranslator(doc, getLanguage) {
    const texts = new WeakMap(), attributes = new WeakMap();
    const skip = el => !el || !!el.closest('script,style,[contenteditable]:not([contenteditable="false"]),[translate="no"],[data-no-i18n]');
    function update(current, previous) {
      // A renderer may have replaced a translated value on the same node.
      const original = previous && current === previous.rendered ? previous.original : current;
      return { original, rendered: translate(original, getLanguage()) };
    }
    function text(node) {
      if (skip(node.parentElement) || node.parentElement.closest('textarea,input')) return;
      const entry = update(node.data, texts.get(node));
      texts.set(node, entry);
      if (node.data !== entry.rendered) node.data = entry.rendered;
    }
    function element(el) {
      if (skip(el)) return;
      let stored = attributes.get(el);
      if (!stored) { stored = {}; attributes.set(el, stored); }
      for (const attr of attrs) {
        if (!el.hasAttribute(attr)) { delete stored[attr]; continue; }
        const current = el.getAttribute(attr);
        stored[attr] = update(current, stored[attr]);
        if (current !== stored[attr].rendered) el.setAttribute(attr, stored[attr].rendered);
      }
    }
    function process(node) {
      if (!node) return;
      if (node.nodeType === 3) { text(node); return; }
      if (node.nodeType !== 1 || skip(node)) return;
      element(node);
      const walker = doc.createTreeWalker(node, 4);
      let child; while ((child = walker.nextNode())) text(child);
      node.querySelectorAll(attrs.map(a => '[' + a + ']').join(',')).forEach(element);
    }
    const observer = new doc.defaultView.MutationObserver(records => {
      for (const record of records) {
        if (record.type === 'characterData') text(record.target);
        else if (record.type === 'attributes') element(record.target);
        else record.addedNodes.forEach(process);
      }
    });
    observer.observe(doc.documentElement, { childList:true, subtree:true, characterData:true, attributes:true, attributeFilter:attrs });
    process(doc.documentElement);
    return { process, disconnect:() => observer.disconnect() };
  }
  root.SamiI18n = { translate, createTranslator };
})(globalThis);