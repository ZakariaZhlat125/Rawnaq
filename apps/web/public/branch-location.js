/* Shared by the desktop picker and mobile booking/gift branch selectors. */
window.SamiBranchLocation = (() => {
  function coordinates(value) {
    const latitude = value?.latitude ?? value?.address?.latitude
    const longitude = value?.longitude ?? value?.address?.longitude
    const valid = n => (typeof n === 'number' || typeof n === 'string') && String(n).trim() !== '' && Number.isFinite(Number(n))
    if (!valid(latitude) || !valid(longitude)) return null
    const lat = Number(latitude), lng = Number(longitude)
    if (Math.abs(lat) > 90 || Math.abs(lng) > 180) return null
    return { latitude: lat, longitude: lng }
  }
  function distance(from, to) {
    const a = coordinates(from), b = coordinates(to)
    if (!a || !b) return null
    const radians = degrees => degrees * Math.PI / 180
    const h = Math.sin(radians(b.latitude - a.latitude) / 2) ** 2
      + Math.cos(radians(a.latitude)) * Math.cos(radians(b.latitude)) * Math.sin(radians(b.longitude - a.longitude) / 2) ** 2
    return 6371.0088 * 2 * Math.asin(Math.sqrt(Math.min(1, Math.max(0, h))))
  }
  function rank(branches, position) {
    const result = branches.map(branch => ({ ...branch, distanceKm: branch.home ? null : distance(position, branch), nearest: false }))
    if (!coordinates(position)) return result
    result.sort((a, b) => (a.distanceKm ?? Infinity) - (b.distanceKm ?? Infinity))
    if (result[0]?.distanceKm != null) result[0].nearest = true
    return result
  }
  function format(km, language = 'ar') {
    if (km == null || !Number.isFinite(km)) return language === 'en' ? 'Distance unavailable' : 'المسافة غير متاحة'
    const number = new Intl.NumberFormat(language, { maximumFractionDigits: km < 1 ? 0 : 1 })
    return km < 1
      ? `${number.format(Math.round(km * 1000))} ${language === 'en' ? 'm away (approx.)' : 'م تقريبًا'}`
      : `${number.format(km)} ${language === 'en' ? 'km away (approx.)' : 'كم تقريبًا'}`
  }
  function locate() {
    return new Promise((resolve, reject) => {
      if (!window.isSecureContext) return reject({ code: 'insecure' })
      if (!navigator.geolocation) return reject({ code: 'unsupported' })
      navigator.geolocation.getCurrentPosition(position => {
        const result = coordinates(position.coords)
        if (!result) return reject({ code: 2 })
        resolve({ ...result, accuracy: position.coords.accuracy })
      }, reject, { enableHighAccuracy: true, timeout: 12000, maximumAge: 60000 })
    })
  }
  function errorMessage(error, language = 'ar') {
    const messages = {
      1: ['لم يُسمح بالوصول إلى موقعك. فعّل إذن الموقع أو اختر الفرع يدويًا.', 'Location permission denied. Enable it or choose a branch manually.'],
      2: ['تعذّر تحديد موقعك. حاول مجددًا أو اختر الفرع يدويًا.', 'Location unavailable. Retry or choose a branch manually.'],
      3: ['انتهت مهلة تحديد الموقع. حاول مجددًا أو اختر الفرع يدويًا.', 'Location request timed out. Retry or choose a branch manually.'],
      insecure: ['تحديد الموقع يتطلب اتصالًا آمنًا. يمكنك اختيار الفرع يدويًا.', 'Location requires a secure connection. You can select a branch manually.'],
      unsupported: ['متصفحك لا يدعم تحديد الموقع. اختر الفرع يدويًا.', 'Your browser does not support location. Select a branch manually.'],
    }
    return (messages[error?.code] || messages[2])[language === 'en' ? 1 : 0]
  }
  return { coordinates, distance, rank, format, locate, errorMessage }
})()
