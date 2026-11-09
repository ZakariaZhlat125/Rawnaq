(function (root) {
  function points(settings, branchId, date, time) {
    if (!branchId || !/^\d{4}-\d{2}-\d{2}$/.test(date || '') || !/^([01]\d|2[0-3]):[0-5]\d$/.test(time || '')) return 0;
    const day = new Date(date + 'T00:00:00Z');
    if (!Number.isFinite(day.getTime()) || day.toISOString().slice(0, 10) !== date) return 0;
    const config = settings.find(row => String(row.branch_id) === String(branchId));
    if (!config?.enabled || Number(config.points) <= 0) return 0;
    return (config.hours?.[day.getUTCDay()] || []).map(Number).includes(Number(time.slice(0, 2))) ? Number(config.points) : 0;
  }
  async function load(apiBase) {
    const controller = new AbortController(), timer = setTimeout(() => controller.abort(), 10000);
    try {
      const response = await (root.SamiPublicApi?.fetch || fetch)(String(apiBase).replace(/\/$/, '') + '/quiet-hours', {
        headers: { Accept: 'application/json' }, signal: controller.signal, cache: 'no-store',
      });
      if (!response.ok) throw Error('Unable to load quiet hours');
      const payload = await response.json();
      return Array.isArray(payload.data) ? payload.data : [];
    } finally { clearTimeout(timer); }
  }
  root.SamiQuietHours = { points, load };
})(globalThis);
