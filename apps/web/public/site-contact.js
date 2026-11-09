(function (root) {
  function normalizeNumber(value) {
    let number = String(value ?? '').trim()
      .replace(/[٠-٩]/g, digit => String(digit.charCodeAt(0) - 1632))
      .replace(/[۰-۹]/g, digit => String(digit.charCodeAt(0) - 1776));
    if (!/^\+?[0-9 ()-]*$/.test(number)) return '';
    number = number.replace(/[+ ()-]/g, '').replace(/^00/, '');
    return /^[1-9][0-9]{6,14}$/.test(number) ? number : '';
  }

  async function load(endpoint) {
    for (let attempt = 0; attempt < 3; attempt++) {
      try {
        const response = await (root.SamiPublicApi?.fetch || fetch)(endpoint, { headers: { Accept: 'application/json' }, cache: 'no-store' });
        if (!response.ok) throw new Error(`Contact API returned ${response.status}`);
        const payload = await response.json();
        if (!payload.data || !Object.hasOwn(payload.data, 'whatsapp_number')) throw new Error('Contact API response is missing whatsapp_number');
        return normalizeNumber(payload.data.whatsapp_number);
      } catch (error) {
        if (attempt === 2) throw error;
        await new Promise(resolve => setTimeout(resolve, 1200));
      }
    }
  }
  root.SamiSiteContact = { normalizeNumber, load };
})(globalThis);
