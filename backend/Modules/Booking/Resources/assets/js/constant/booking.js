export const MODULE = 'bookings'
// export const INDEX_URL = () => {return {path: `${MODULE}/index_list`, method: 'GET'}}
export const INDEX_URL = ({ user_type, page = 1, per_page = 8, employee_id = null, employee_ids = null, branch_id = null, date = null, date_start = null, date_end = null, start_date_time = null, service_duration = 0 }) => {
  let path = `${MODULE}/index_list?page=${page}&per_page=${per_page}`;
  
  if (employee_id) {
    path += `&employee_id=${employee_id}`;
  }
  if (employee_ids) {
    path += `&employee_ids=${encodeURIComponent(Array.isArray(employee_ids) ? employee_ids.join(',') : employee_ids)}`;
  }
  if (branch_id !== null && branch_id !== undefined && branch_id !== '') {
    path += `&branch_id=${branch_id}`;
  }
  if (date) {
    path += `&date=${date}`;
  }
  if (date_start) {
    path += `&date_start=${date_start}`;
  }
  if (date_end) {
    path += `&date_end=${date_end}`;
  }
  if (start_date_time) {
    path += `&start_date_time=${encodeURIComponent(start_date_time)}`;
  }
  if (service_duration) {
    path += `&service_duration=${service_duration}`;
  }

  return {
    path,
    method: 'GET',
  };
};

export const EDIT_URL = (id) => {return {path: `${MODULE}/${id}/edit`, method: 'GET'}}
export const BOOKING_DETAIL = (id) => {return {path: `${MODULE}/${id}`, method: 'GET'}}
export const STORE_URL = () => {return {path: `${MODULE}`, method: 'POST'}}
export const UPDATE_URL = (id) => {return {path: `${MODULE}/${id}`, method: 'PUT'}}
export const CHECKOUT_URL = (id) => {return {path: `${MODULE}/${id}/checkout`, method: 'PUT'}}
export const PAYMENT_CREATE_URL = ({ booking_id ,userPackageserviceIds }) => {return {path: `${MODULE}/payment-create?booking_id=${booking_id}&userPackageserviceIds=${userPackageserviceIds}`, method: 'GET'}}
export const PAYMENT_PUT_URL = (booking_id) => {return {path: `${MODULE}/booking-payment/${booking_id}`, method: 'PUT'}}
export const UPDATE_STATUS = (id) => {return {path: `${MODULE}/update-status/${id}`, method: 'POST'}}
export const UPDATE_PAYMENT_STATUS = (id) => {return {path: `${MODULE}/update-payment-status/${id}`, method: 'POST'}}
export const EMPLOYEE_ORDER_URL = () => {return {path: `${MODULE}/employee-order`, method: 'POST'}}
export const CUSTOMER_LIST = () => {return {path: `users/user-list?role=user`, method: 'GET'}}
export const SERVICE_LIST = ({id: employee_id, branch_id}) => {return {path: `${MODULE}/services-index_list?employee_id=${employee_id}&branch_id=${branch_id}`, method: 'GET'}}
export const SLOT_LIST = ({date, employee_id = null, branch_id, service_duration = 0, booking_id = null}) => {return { path: `${MODULE}/slots?date=${date}&branch_id=${branch_id}&employee_id=${employee_id || ''}&service_duration=${service_duration}&booking_id=${booking_id || ''}`, method: 'GET',}}
export const UPDATE_PAYMENT_DATA = (booking_transaction_id) => {return {path: `${MODULE}/booking-payment-update/${booking_transaction_id}`, method: 'PUT'}}
export const STRIPE_PAYMENT_DATA = () => {return {path: `${MODULE}/stripe-payment`, method: 'POST'}}
export const coupon_validate = () => {return {path: `promotions/coupon-validate`, method: 'PUT'}}

// Product Module
export const PRODUCT_LIST = () => {return {path: `products/index_list_with_varient`, method: 'GET'}}
// Package Module
export const PACKAGE_LIST = ({branch_id}) => { return {path: `package/index_list?branch_id=${branch_id}`, method: 'GET'}};
export const USER_PACKAGE_LIST = (id) => ({ path: `package/user_package_list/${id}`, method: 'GET' });
