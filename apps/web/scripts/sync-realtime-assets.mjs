import { copyFileSync, mkdirSync } from 'node:fs'
const frontend = new URL('../public/', import.meta.url)
const backend = new URL('../../../sami-care/public/js/', import.meta.url)
mkdirSync(new URL('vendor/', frontend), { recursive: true })
mkdirSync(new URL('vendor/', backend), { recursive: true })
copyFileSync(new URL('../node_modules/pusher-js/dist/web/pusher.min.js', import.meta.url), new URL('vendor/pusher.min.js', frontend))
copyFileSync(new URL('vendor/pusher.min.js', frontend), new URL('vendor/pusher.min.js', backend))
copyFileSync(new URL('realtime-notifications.js', frontend), new URL('realtime-notifications.js', backend))
console.log('Pusher and shared realtime assets synced for frontend and dashboard')
