export const MODULE = 'bussinesshours'
export const LISTING_URL = ({ branch_id, shift_id }) => {return {path: `bussinesshours/index_list?branch_id=${branch_id}&shift_id=${shift_id}`, method: 'GET'}}
export const STORE_URL = () => {return {path: `${MODULE}`, method: 'POST'}}