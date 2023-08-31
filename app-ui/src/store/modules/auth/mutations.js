import { AUTH } from "./types"
import { LOCAL_STORAGE } from "../../../services/enums/localStorageEnums"
import createState from "./state"
import { resetState } from "../../utils"
import { ability } from "../../../config"
import { ACL } from "../../../services/enums/aclEnums"

const userSettingsFallback = {
  language: "en",
  darkMode: false,
  show: false,
}

const updateAuth = (state, payload) => {
  localStorage.setItem(LOCAL_STORAGE.USER_TOKEN, payload.token || null)
  if (payload.user) {
    state.email = payload.user.email
    state.id = payload.user.id
  } else {
    state.email = payload.email
    state.id = payload.id
  }

  state.token = payload.token
  ability.update([
    { action: "read", subject: ACL.DASHBOARD_PAGE },
    { action: "read", subject: ACL.USERS_PAGE },
  ])
}

export default {
  [AUTH.MUTATIONS.LOGIN_RESPONSE]: (state, payload) => {
    updateAuth(state, payload)
    state.checked = false
    if (!payload.settings || payload.settings.length === 0) {
      localStorage.setItem(
        LOCAL_STORAGE.USER_SETTINGS,
        JSON.stringify(userSettingsFallback)
      )
    } else {
      localStorage.setItem(
        LOCAL_STORAGE.USER_SETTINGS,
        JSON.stringify(payload.settings)
      )
    }
  },
  [AUTH.MUTATIONS.CHECK_LOGGED_RESPONSE]: (state, payload) => {
    updateAuth(state, payload)
    state.checked = true
  },
  [AUTH.MUTATIONS.LOGOUT_RESPONSE]: () => {
    localStorage.removeItem(LOCAL_STORAGE.USER_TOKEN)
  },
  [AUTH.MUTATIONS.UPDATE_CONTACT_RESPONSE]: (state, payload) => {
    state.data.contact = payload
  },
  [AUTH.MUTATIONS.RESET]: (state) => {
    resetState(state, createState())
  },
}
