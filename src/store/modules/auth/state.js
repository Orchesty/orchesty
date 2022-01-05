import { LOCAL_STORAGE } from '../../../services/enums/localStorageEnums'

export default () => ({
  token: localStorage.getItem(LOCAL_STORAGE.USER_TOKEN) || null,
  user: null,
  checked: false,
})
