import { LOCAL_STORAGE } from '@/services/enums/localStorageEnums'

export default () => ({
  user: null,
  settings: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)) || null,
})
