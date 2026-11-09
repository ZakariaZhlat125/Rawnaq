export function mountBrowserPush(container, apiBase) {
  const button = container.querySelector('.sn-push-toggle') || document.createElement('button')
  const status = container.querySelector('.sn-push-status') || document.createElement('p')
  status.setAttribute('role', 'status')
  status.className = 'sn-push-status'
  button.className = 'sn-push-toggle'
  button.hidden = false
  button.textContent = 'تفعيل إشعارات المتصفح'
  button.type = 'button'
  if (!button.parentNode) container.append(button)
  if (!status.parentNode) container.append(status)
  let token = null, settings, registration, busy = false, disposed = false
  const supported = window.isSecureContext && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window
  button.disabled = true
  if (!supported) status.textContent = 'إشعارات المتصفح غير مدعومة في وضع التصفح الحالي. على iPhone أضف الموقع للشاشة الرئيسية ثم افتحه من الأيقونة. تأكد أيضًا من استخدام اتصال HTTPS.'
  const request = async (path, method = 'GET', body, credential = token) => {
    const response = await fetch(apiBase + '/webpush/' + path, { method, headers: { Accept: 'application/json', 'Content-Type': 'application/json', Authorization: `Bearer ${credential}` }, body: body ? JSON.stringify(body) : undefined, cache: 'no-store' })
    if (!response.ok) throw new Error('push request failed')
    return response.json()
  }
  const keyBytes = key => Uint8Array.from(atob(key.replace(/-/g, '+').replace(/_/g, '/') + '='.repeat((4 - key.length % 4) % 4)), c => c.charCodeAt(0))
  async function removeSubscription(credential) {
    const sub = await registration?.pushManager.getSubscription()
    if (!sub) return
    try { await request('subscription', 'DELETE', { endpoint: sub.endpoint }, credential) } catch { /* Browser unsubscribe still revokes delivery. */ }
    await sub.unsubscribe()
    for (const notification of await registration.getNotifications()) notification.close()
  }
  function label(active) { button.textContent = active ? 'إيقاف إشعارات المتصفح' : 'تفعيل إشعارات المتصفح' }
  button.onclick = async () => {
    if (busy || !token || !settings?.enabled) return
    busy = true; button.disabled = true; status.textContent = ''
    const current = token
    const permissionRequest = Notification.permission === 'default' ? Notification.requestPermission() : Promise.resolve(Notification.permission)
    try {
      const existing = await registration.pushManager.getSubscription()
      if (existing) { await removeSubscription(current); label(false); status.textContent = 'تم إيقاف الإشعارات على هذا المتصفح.'; return }
      // Request permission only in response to the customer's explicit click.
      const permission = await permissionRequest
      if (permission !== 'granted') { status.textContent = 'لم يتم السماح بالإشعارات. يمكنك تغيير الإذن من إعدادات المتصفح.'; return }
      if (current !== token || disposed) return
      const sub = await registration.pushManager.subscribe({ userVisibleOnly: true, applicationServerKey: keyBytes(settings.public_key) })
      try { await request('subscription', 'POST', sub.toJSON(), current) } catch (error) { await sub.unsubscribe(); throw error }
      if (current !== token || disposed) { await removeSubscription(current); return }
      label(true); status.textContent = 'تم التفعيل. ستصلك الإشعارات حتى عندما تكون خارج الموقع.'
    } catch { status.textContent = 'تعذر تفعيل الإشعارات. حاول مجددًا. على iPhone افتح الموقع من أيقونته بعد إضافته للشاشة الرئيسية.' }
    finally { busy = false; button.disabled = false }
  }
  let chain = Promise.resolve()
  return {
    sync(next) {
      const previous = token; token = next
      chain = chain.then(async () => {
        if (!supported || disposed) return
        button.disabled = true
        registration = await navigator.serviceWorker.register('/sw.js', { scope: '/', updateViaCache: 'none' })
        await navigator.serviceWorker.ready
        if (previous && previous !== next) await removeSubscription(previous)
        if (!next || next !== token) return
        settings = await request('config', 'GET', undefined, next)
        if (!settings.enabled) {
          status.textContent = 'إشعارات المتصفح غير مفعّلة بالموقع حاليًا.'
          return
        }
        status.textContent = ''
        const sub = await registration.pushManager.getSubscription()
        if (sub) {
          try { await request('subscription', 'POST', sub.toJSON(), next); label(true) }
          catch { await sub.unsubscribe(); label(false) }
        } else label(false)
      }).catch(() => { settings = null; status.textContent = 'تعذر تجهيز إشعارات المتصفح. أعد تحميل الصفحة وحاول مجددًا.' }).finally(() => { button.disabled = !supported || !token || !settings?.enabled || !registration?.active })
    },
    destroy() { disposed = true; button.remove(); status.remove() },
  }
}
