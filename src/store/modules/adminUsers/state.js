import { LOCAL_STORAGE } from '@/enums'

export default () => ({
  user: null,
  settings: JSON.parse(localStorage.getItem(LOCAL_STORAGE.USER_SETTINGS)) || null,
})
