import { LOCAL_STORAGE } from '../../../enums'

export default () => ({
  token: localStorage.getItem(LOCAL_STORAGE.USER_TOKEN) || null,
  user: null,
  checked: false,
})
