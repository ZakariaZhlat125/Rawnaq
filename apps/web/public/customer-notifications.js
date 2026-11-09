/* Shared inbox for the Vue shell and the standalone mobile page. */
window.mountSamiNotifications = function ({ apiBase, onCount = () => {}, hideButton = false }) {
  const root = document.createElement('div')
  root.dir = 'rtl'
  root.innerHTML = `<style>
    .sn-root{position:fixed;z-index:10050;top:84px;left:16px;font-family:inherit;color:#30291e;direction:rtl}
    .sn-root button{font:inherit;cursor:pointer;border:1px solid #ded4c3;border-radius:12px;background:#fffaf2;color:#30291e;padding:10px 14px}
    .sn-root [hidden]{display:none!important}.sn-bell{box-shadow:0 4px 18px #0002}
    .sn-panel{width:min(380px,calc(100vw - 32px));max-height:70dvh;overflow:auto;background:#fffdf8;border:1px solid #d9ccb4;border-radius:18px;box-shadow:0 12px 44px #0003;padding:16px;margin-top:8px}
    .sn-head{display:flex;justify-content:space-between;align-items:center;gap:10px}.sn-head h2{font-size:19px;margin:0}.sn-tools{margin:12px 0;display:flex;gap:8px}
    .sn-item{display:block;width:100%;text-align:right;margin:8px 0}.sn-item.unread{background:#f3ead8;border-color:#b89554}.sn-item strong,.sn-item span,.sn-item time{display:block}.sn-item span{font-size:14px;line-height:1.7;margin:6px 0}.sn-item time{font-size:12px;color:#716552}.sn-status{font-size:13px;line-height:1.6}.sn-toast{max-width:340px;padding:12px;background:#30291e;color:white;border-radius:12px;margin-top:8px}
  </style><div class="sn-root" hidden><button class="sn-bell" aria-label="الإشعارات" aria-expanded="false"><i class="sami-bell-icon" aria-hidden="true"></i><span class="sn-count">0</span></button><section class="sn-panel" aria-label="الإشعارات" hidden><div class="sn-head"><h2>الإشعارات</h2><button class="sn-close" aria-label="إغلاق الإشعارات">✕</button></div><div class="sn-tools"><button class="sn-all">قراءة الكل</button><button class="sn-refresh">تحديث</button></div><p class="sn-status" role="status"></p><div class="sn-list"></div><button class="sn-more" hidden>عرض المزيد</button></section><div class="sn-toast" role="status" hidden></div></div>`
  document.body.append(root)
  const el = selector => root.querySelector(selector)
  const shell = el('.sn-root'), panel = el('.sn-panel'), bell = el('.sn-bell')
  bell.hidden = hideButton
  let realtime, realtimeModule;
  let token = null, generation = 0, streamController, timer, toastTimer, ringTimer, watchdog, disposed = false
  let items = [], seen = new Set(), initialized = false, page = 1, lastPage = 1, failures = 0
  const base = apiBase.replace(/\/$/, '')
  // Keep push controls outside the message list and give them their own status.
  // They must survive both inbox refreshes and delayed configuration responses.
  const pushControls = document.createElement('div')
  pushControls.className = 'sn-push-controls'
  pushControls.innerHTML = '<button type="button" class="sn-push-toggle" disabled>تفعيل إشعارات المتصفح</button><p class="sn-push-status" role="status">جارٍ تجهيز إشعارات المتصفح…</p>'
  panel.insertBefore(pushControls, el('.sn-list'))
  let browserPush
  import('/browser-push.js?v=20261015-3').then(module => {
    if (disposed) return
    browserPush = module.mountBrowserPush(pushControls, base)
    browserPush.sync(token)
  }).catch(() => {
    if (disposed) return
    el('.sn-push-status').textContent = 'تعذر تحميل إعدادات إشعارات المتصفح. أعد تحميل الصفحة وحاول مجددًا.'
  })
  const readToken = () => { try { return localStorage.getItem('samiAuthToken') } catch { return null } }
  const headers = () => ({ Accept: 'application/json', Authorization: `Bearer ${token}` })
  const soundButton = document.createElement('button')
  soundButton.type = 'button'; soundButton.disabled = true
  const updateSoundLabel = () => {
    const off = realtimeModule?.notificationSound.muted()
    soundButton.textContent = off ? '\u062a\u0641\u0639\u064a\u0644 \u0635\u0648\u062a \u0627\u0644\u0625\u0634\u0639\u0627\u0631\u0627\u062a' : '\u0643\u062a\u0645 \u0635\u0648\u062a \u0627\u0644\u0625\u0634\u0639\u0627\u0631\u0627\u062a'
    soundButton.setAttribute('aria-pressed', String(!off))
  }
  el('.sn-tools').append(soundButton)
  const realtimePromise = import('/realtime-notifications.js?v=20261024-1').then(module => {
    realtimeModule = module; soundButton.disabled = false; updateSoundLabel(); return module
  }).catch(() => null)
  soundButton.onclick = () => { realtimeModule?.notificationSound.toggle(); updateSoundLabel() }

  const message = item => {
    const raw = item.data?.data?.message || item.data?.message || ''
    // Render legacy HTML templates as text, never insert server content as markup.
    const doc = new DOMParser().parseFromString(String(raw), 'text/html')
    return doc.body.textContent || ''
  }
  function render() {
    el('.sn-list').replaceChildren()
    for (const item of items) {
      const button = document.createElement('button')
      button.className = `sn-item${item.read_at ? '' : ' unread'}`
      const title = document.createElement('strong'), body = document.createElement('span'), time = document.createElement('time')
      title.textContent = item.data?.subject || item.data?.data?.type || 'إشعار'
      body.textContent = message(item)
      time.textContent = new Date(item.created_at).toLocaleString('ar-SA')
      button.append(title, body, time)
      button.setAttribute('aria-label', `${title.textContent}${item.read_at ? '' : '، غير مقروء'}`)
      button.onclick = () => mark(`/notifications/${encodeURIComponent(item.id)}/read`)
      el('.sn-list').append(button)
    }
    el('.sn-more').hidden = page >= lastPage
  }
  function accept(payload) {
    const incoming = payload.notification_data || []
    const fresh = incoming.filter(item => !seen.has(item.id) && !item.read_at)
    if (initialized && fresh.length) {
      realtimeModule?.notificationSound.play(fresh[0].id)
      bell.setAttribute('data-arriving', 'true')
      clearTimeout(ringTimer)
      ringTimer = setTimeout(() => bell.setAttribute('data-arriving', 'false'), 900)
      el('.sn-toast').textContent = message(fresh[0])
      el('.sn-toast').hidden = false
      clearTimeout(toastTimer)
      toastTimer = setTimeout(() => { el('.sn-toast').hidden = true }, 6000)
      window.dispatchEvent(new CustomEvent('sami:customer-activity', { detail: fresh }))
    }
    incoming.forEach(item => seen.add(item.id))
    initialized = true
    // Keep older pages visible as new events arrive.
    const ids = new Set(incoming.map(item => item.id))
    items = [...incoming, ...items.filter(item => !ids.has(item.id))]
    lastPage = payload.last_page || 1
    const count = Number(payload.all_unread_count) || 0
    bell.setAttribute('data-unread', count > 0 ? 'true' : 'false')
    el('.sn-count').textContent = count > 99 ? '99+' : String(count)
    onCount(count)
    el('.sn-status').textContent = items.length ? '' : 'لا توجد إشعارات حتى الآن'
    render()
  }
  async function request(path, method = 'GET') {
    const response = await fetch(base + path, { method, headers: headers(), cache: 'no-store' })
    if (!response.ok) throw new Error(String(response.status))
    return response.json()
  }
  async function refresh() {
    const current = generation
    try {
      const payload = await request('/notification-list')
      if (current === generation && !disposed) accept(payload)
    } catch { if (current === generation) el('.sn-status').textContent = 'تعذر الاتصال. سنعيد المحاولة تلقائيًا.' }
  }
  async function mark(path) {
    const current = generation
    try {
      await request(path, 'POST')
      if (current !== generation || disposed) return
      if (path.endsWith('read-all')) items.forEach(item => { item.read_at ||= new Date().toISOString() })
      else {
        const id = decodeURIComponent(path.split('/')[2])
        const item = items.find(item => item.id === id)
        if (item) item.read_at ||= new Date().toISOString()
      }
      await refresh()
    } catch { el('.sn-status').textContent = 'تعذر تحديث حالة القراءة. حاول مجددًا.' }
  }
  async function connect(current) {
    const module = await realtimePromise
    if (disposed || !token || current !== generation) return
    realtime?.destroy()
    if (!module) { timer = setTimeout(async () => { await refresh(); connect(current) }, 30000); return }
    realtime = module.mountRealtime({ configUrl: base + '/notifications/realtime-config', headers,
      onEvent: id => {
        if (disposed || current !== generation) return
        if (id && !seen.has(id)) module.notificationSound.play(id)
        refresh()
      },
    })
  }

  function sync() {
    const next = readToken()
    if (next === token) return false
    generation++
    realtime?.destroy()
    clearTimeout(timer)
    clearTimeout(toastTimer)
    clearTimeout(ringTimer)
    bell.setAttribute('data-arriving', 'false')
    bell.setAttribute('data-unread', 'false')
    token = next
    browserPush?.sync(next)
    items = []; seen = new Set(); initialized = false; page = 1; lastPage = 1; failures = 0
    shell.hidden = !token; panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); el('.sn-toast').hidden = true
    el('.sn-count').textContent = '0'
    onCount(0); render()
    if (token) { refresh(); connect(generation) }
    return true
  }
  function open() {
    sync()
    if (!token) return
    panel.hidden = false; bell.setAttribute('aria-expanded', 'true'); el('.sn-close').focus(); refresh()
  }
  function close() { panel.hidden = true; bell.setAttribute('aria-expanded', 'false'); if (!bell.hidden) bell.focus() }
  function visibility() {
    if (!document.hidden && !sync() && token) refresh()
  }

  const keydown = event => { if (event.key === 'Escape' && !panel.hidden) close() }
  bell.onclick = () => panel.hidden ? open() : close()
  el('.sn-close').onclick = close
  el('.sn-all').onclick = () => mark('/notifications/read-all')
  el('.sn-refresh').onclick = refresh
  el('.sn-more').onclick = async () => {
    const current = generation, button = el('.sn-more')
    button.disabled = true
    try {
      const payload = await request(`/notification-list?page=${page + 1}`)
      if (current !== generation || disposed) return
      page = payload.current_page
      const ids = new Set(items.map(item => item.id))
      items.push(...payload.notification_data.filter(item => !ids.has(item.id)))
      lastPage = payload.last_page; render()
    } catch { el('.sn-status').textContent = 'تعذر تحميل المزيد. حاول مجددًا.' }
    finally { button.disabled = false }
  }
  const authTimer = setInterval(sync, 1000)
  window.addEventListener('storage', sync)
  document.addEventListener('visibilitychange', visibility)
  document.addEventListener('keydown', keydown)
  sync()
  return {
    open,
    setHideButton(value) { bell.hidden = value },
    destroy() {
      browserPush?.destroy()
      disposed = true; generation++; realtime?.destroy()
      clearInterval(authTimer); clearTimeout(timer); clearTimeout(watchdog); clearTimeout(toastTimer); clearTimeout(ringTimer)
      window.removeEventListener('storage', sync)
      document.removeEventListener('visibilitychange', visibility)
      document.removeEventListener('keydown', keydown)
      root.remove()
    },
  }
}
