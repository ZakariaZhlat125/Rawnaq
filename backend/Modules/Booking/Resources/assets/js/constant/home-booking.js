const MODULE = 'bookings/home-bookings'

export const SERVICE_GROUPS_URL = () => {
  return { path: '/api/service-groups?is_home=1', method: 'GET' }
}

export const SERVICES_URL = ({ service_group_home_id }) => {
  return { path: `/api/services/${service_group_home_id}/0/bookings`, method: 'GET' }
}

export const STAFF_URL = ({ service_home_id }) => {
  return { path: `/api/staff?branch_id=0&service_id=${service_home_id}`, method: 'GET' }
}

export const AVAILABLE_TIMES_URL = ({ date, staff_home_id, duration }) => {
  return { path: `/api/available/${date}/${staff_home_id}?Increasing=${duration}`, method: 'GET' }
}

export const STORE_URL = () => {
  return { path: `${MODULE}`, method: 'POST' }
}
