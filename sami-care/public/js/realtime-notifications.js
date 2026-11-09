/* Shared by the dashboard, desktop shell and standalone mobile inbox. */
let pusherLoader
function loadPusher() {
  if (window.Pusher) return Promise.resolve(window.Pusher)
  return pusherLoader ||= new Promise((resolve, reject) => {
    const script = document.createElement('script')
    script.src = new URL('./vendor/pusher.min.js', import.meta.url).href
    script.onload = () => resolve(window.Pusher)
    script.onerror = () => { pusherLoader = null; script.remove(); reject(new Error('Pusher unavailable')) }
    document.head.append(script)
  })
}

let audio, lastSound = 0
const played = new Set()
const audioDocuments = new WeakSet()
function muted() { try { return localStorage.getItem('samiNotificationSoundMuted') === '1' } catch { return false } }
function unlock() {
  if (muted()) return
  try {
    const Context = window.AudioContext || window.webkitAudioContext
    if (!Context) return
    audio ||= new Context()
    if (audio.state === 'suspended') audio.resume().catch(() => {})
  } catch { /* Browser audio policy: never block the inbox. */ }
}
function attachAudio(doc) {
  if (!doc || audioDocuments.has(doc)) return
  audioDocuments.add(doc)
  doc.addEventListener('pointerdown', unlock, { passive: true, capture: true })
  doc.addEventListener('keydown', unlock, { capture: true })
}
function attachFrames() {
  attachAudio(document)
  document.querySelectorAll('iframe').forEach(frame => { try { attachAudio(frame.contentDocument) } catch {} })
}
attachFrames()
window.addEventListener('load', attachFrames, true)
export const notificationSound = {
  muted,
  toggle() {
    const next = !muted()
    try { localStorage.setItem('samiNotificationSoundMuted', next ? '1' : '0') } catch {}
    if (!next) unlock()
    return next
  },
  play(id) {
    if (!id || played.has(id)) return
    played.add(id)
    if (played.size > 1000) played.delete(played.values().next().value)
    const now = Date.now()
    if (muted() || !audio || audio.state !== 'running' || now - lastSound < 1000) return
    lastSound = now
    try {
      // A short, soft three-note chime, generated locally with no audio download.
      ;[659.25, 830.61, 987.77].forEach((frequency, i) => {
        const start = audio.currentTime + i * 0.12
        const oscillator = audio.createOscillator(), gain = audio.createGain()
        oscillator.type = 'sine'; oscillator.frequency.value = frequency
        gain.gain.setValueAtTime(0, start)
        gain.gain.linearRampToValueAtTime(0.07, start + 0.015)
        gain.gain.exponentialRampToValueAtTime(0.001, start + 0.36)
        oscillator.connect(gain); gain.connect(audio.destination)
        oscillator.start(start); oscillator.stop(start + 0.4)
      })
    } catch {}
  },
}

export function mountRealtime({ configUrl, headers = () => ({}), onEvent, onConnected = () => {}, onFallback = () => {} }) {
  let client, channel, disposed = false, connected = false, poll, retry, checking = false
  const seen = new Set()
  const fallback = () => {
    connected = false; onFallback()
    poll ||= setInterval(() => { if (!disposed && !document.hidden) onEvent(null) }, 30000)
  }
  async function start() {
    if (disposed || checking) return
    checking = true
    try {
      const response = await fetch(configUrl, { headers: { Accept: 'application/json', ...headers() }, credentials: 'same-origin', cache: 'no-store' })
      if (!response.ok) throw new Error('Configuration unavailable')
      const config = await response.json()
      if (disposed) return
      if (!config.enabled) { fallback(); return }
      const Pusher = await loadPusher()
      if (disposed) return
      client = new Pusher(config.key, { cluster: config.cluster, forceTLS: true,
        channelAuthorization: { customHandler: async ({ socketId, channelName }, callback) => {
          try {
            if (disposed) return
            const auth = await fetch(config.auth_url, { method: 'POST', credentials: 'same-origin', cache: 'no-store',
              headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...headers() },
              body: JSON.stringify({ socket_id: socketId, channel_name: channelName }) })
            if (!auth.ok) throw new Error('Channel authorization failed')
            const result = await auth.json()
            if (!disposed) callback(null, result)
          } catch (error) { if (!disposed) callback(error, null) }
        } },
      })
      channel = client.subscribe(config.channel)
      channel.bind('notification.created', event => {
        if (disposed || !event?.id || seen.has(event.id)) return
        seen.add(event.id); if (seen.size > 1000) seen.delete(seen.values().next().value)
        onEvent(event.id)
      })
      channel.bind('pusher:subscription_succeeded', () => {
        if (disposed) return
        const wasConnected = connected
        connected = true; clearInterval(poll); poll = null
        onConnected()
        if (!wasConnected) onEvent(null) // Catch up on events missed while disconnected, without inventing an event ID.
      })
      channel.bind('pusher:subscription_error', fallback)
      client.connection.bind('state_change', ({ current }) => { if (current !== 'connected') fallback() })
      // The SDK reconnects automatically; polling covers blocked WebSockets and outages.
      fallback()
    } catch { if (!disposed) fallback() }
    finally {
      checking = false
      if (!disposed && !client) retry = setTimeout(start, 60000)
    }
  }
  start()
  return { destroy() {
    disposed = true; clearInterval(poll); clearTimeout(retry)
    channel?.unbind_all(); client?.connection.unbind_all(); client?.disconnect()
  } }
}
