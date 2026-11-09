// A zero branch price is valid; only missing or invalid prices use the base price.
export function servicePrice(service, location) {
  const branch = !location?.home && location?.id != null
    ? service.branches?.find(branch => String(branch.branch_id) === String(location.id))
    : null
  const value = branch?.service_price
  return value !== null && value !== undefined && value !== '' && Number.isFinite(Number(value))
    ? Number(value)
    : Number(service.default_price ?? service.price ?? 0)
}
