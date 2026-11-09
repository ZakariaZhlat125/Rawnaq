const appBaseUrl = import.meta.env.BASE_URL || '/'
const normalizedBaseUrl = appBaseUrl.endsWith('/') ? appBaseUrl : `${appBaseUrl}/`
const absoluteUrlPattern = /^(?:[a-z][a-z\d+\-.]*:)?\/\//i

export function assetPath(path) {
  if (!path || absoluteUrlPattern.test(path) || path.startsWith('data:') || path.startsWith('#')) {
    return path
  }

  return `${normalizedBaseUrl}${path.replace(/^\/+/, '')}`
}

const apiOrigin = (import.meta.env.VITE_API_BASE_URL || '').replace(/\/api\/?$/, '')

export function resolveApiImage(path) {
  if (!path) return null
  if (/^https?:\/\//.test(path)) return path
  return `${apiOrigin}/${String(path).replace(/^\/+/, '')}`
}

/*
  روابط زي /cafe بتتقدَّم من تطبيق Laravel نفسه، مش من هذا الـSPA —
  لازم يبقى رابط مطلق لأصل الباك إند وإلا هيتلقفه راوتر project-sami
  ويرجّعه للصفحة الرئيسية.
*/
export function resolveBackendUrl(path) {
  return `${apiOrigin}/${String(path).replace(/^\/+/, '')}`
}
