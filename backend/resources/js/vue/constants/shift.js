export const MODULE = 'shift'
export const SHIFT_LIST = () => {
  return { path: `${MODULE}/index_list`, method: 'GET' }
}
export const INDEX_URL = () => {
  return { path: `${MODULE}/index`, method: 'GET' }
}
export const EDIT_URL = (id) => {
  return { path: `${MODULE}/edit/${id}`, method: 'GET' }
}

export const STORE_URL = () => {
  return { path: `${MODULE}/store`, method: 'POST' }
}

export const UPDATE_URL = (id) => {
  return { path: `${MODULE}/update/${id}`, method: 'POST' }
}

export const DELETE_URL = (id) => {
  return { path: `${MODULE}/delete/${id}`, method: 'DELETE' }
}
