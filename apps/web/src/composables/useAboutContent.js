import { inject } from 'vue'
export function useAboutContent() { return inject('aboutContent') }

export function safeAboutUrl(value) {
  try {
    const url = new URL(value)
    return ['https:', 'http:'].includes(url.protocol) ? url.href : ''
  } catch { return '' }
}

export function branchPhone(value) { return String(value || '').replace(/[^+\d]/g, '') }
