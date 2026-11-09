import { createHash } from 'node:crypto'
import { readFileSync, writeFileSync, copyFileSync } from 'node:fs'
const dist = new URL('../dist/', import.meta.url)
const files = ['sw.js', 'pwa.js', 'manifest.webmanifest', 'pwa/offline.html', 'pwa/icon-192.png', 'pwa/icon-512.png', 'pwa/icon-maskable-512.png', 'pwa/apple-touch-icon.png']
const hash = createHash('sha256')
for (const file of files) hash.update(file).update(readFileSync(new URL(file, dist)))
const version = hash.digest('hex').slice(0, 16)
for (const file of ['sw.js', 'pwa.js']) {
  const url = new URL(file, dist)
  const source = readFileSync(url, 'utf8')
  if (!source.includes('__PWA_BUILD__')) throw new Error(`Missing PWA build marker in ${file}`)
  writeFileSync(url, source.replaceAll('__PWA_BUILD__', version))
}
copyFileSync(new URL('index.html', dist), new URL('200.html', dist))
console.log(`PWA build ${version}: manifest, offline page and service worker ready`)