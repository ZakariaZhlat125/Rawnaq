/* Shared by the Vue checkout and the standalone mobile checkout. */
(function (root) {
  const checkoutEnabled = true;
  const enabledGateways = new Set(['cod', 'mada', 'card', 'urpay', 'tabby']);
  const money = value => Math.max(Number(value) || 0, 0);
  function rewards(state, total) {
    total = money(total);
    const wallet = state.useWallet ? Math.min(money(state.walletAmount), money(state.walletBalance), total) : 0;
    const pointValue = money(state.pointValue) || 0.5;
    const maxPoints = Math.min(Math.floor(money(state.loyaltyPoints)), Math.ceil((total - wallet) / pointValue));
    const points = state.useLoyalty ? Math.min(Math.floor(money(state.loyaltyPointsUsed)), maxPoints) : 0;
    const loyalty = Math.min(points * pointValue, total - wallet);
    return { wallet, points, loyalty, maxPoints, payable: Math.max(total - wallet - loyalty, 0), hasRewards: wallet > 0 || points > 0 };
  }
  function methods({ total, walletBalance, payable = total, hasRewards = false }) {
    const deposit = Math.round((money(total) * 0.3 + Number.EPSILON) * 100) / 100;
    const balance = money(walletBalance);
    return [
      { id: 'cod', n: 'الدفع عند الوصول', logo: 'COD', enabled: !hasRewards && balance >= deposit,
        d: hasRewards ? 'غير متاح مع خصم المحفظة أو النقاط' : balance < deposit
          ? `يتطلب عربون 30% (${deposit} ر.س) في المحفظة — رصيدك: ${balance} ر.س`
          : `عربون 30% (${deposit} ر.س) يُخصم من المحفظة والباقي عند الوصول` },
      { id: 'mada', n: 'مدى', d: 'ادفع باستخدام بطاقات مدى', logo: 'مدى', enabled: true },
      { id: 'card', n: 'بطاقات الائتمان والخصم', d: hasRewards ? 'ادفع المتبقي بعد الخصومات' : 'Visa / Mastercard', logo: 'VISA', enabled: true },
      { id: 'urpay', n: 'يورباي', d: hasRewards ? 'ادفع المتبقي بعد الخصومات' : 'الدفع عبر يورباي', logo: 'UrPay', enabled: payable > 0 },
      { id: 'tabby', n: 'تابي', d: 'قسّم دفعاتك عبر تابي', logo: 'tabby', enabled: payable > 0 },
    ].map(method => checkoutEnabled && enabledGateways.has(method.id) ? method : { ...method, enabled: false, d: 'غير متاح حاليًا' });
  }
  function options(state, total) {
    const parts = rewards(state, total);
    return methods({ total, walletBalance: state.walletBalance, ...parts });
  }
  function canPay(state, total) {
    if (!checkoutEnabled) return false;
    return options(state, total).some(m => m.id === state.pay && m.enabled);
  }
  function payment(state, total) {
    if (!canPay(state, total)) throw new Error('اختر وسيلة دفع متاحة لإتمام العملية');
    const parts = rewards(state, total);
    return { gateway: parts.payable <= 0 || state.pay === 'mada' ? 'card' : state.pay, ...(state.pay === 'mada' ? { paymentSource: 'src_sa.mada' } : {}), wallet: parts.wallet > 0, walletAmount: parts.wallet, loyalty: parts.points > 0, loyaltyPoints: parts.points };
  }
  root.SamiPaymentPolicy = { checkoutEnabled, rewards, methods, options, canPay, payment };
})(globalThis);
