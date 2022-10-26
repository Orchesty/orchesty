import { transformUser } from "@/firebase"
import {
  alerts,
  callApi,
  i18n,
  router,
  saveUserWithTokenToStore,
} from "@/utils"
import {
  getAuth,
  signInWithEmailAndPassword,
  updatePassword,
  User,
  updateProfile,
  sendPasswordResetEmail,
  signOut,
} from "firebase/auth"
import { TLoginForm, TResetPasswordForm } from "../../../components/auth/types"
import { Actions } from "../../../types"
import { AuthState } from "./state"
import { AuthActions, AuthMutations } from "./types"
import { UpdateUserInfo } from "@/types/CurrentUser"
import { LocalStorage, Routes } from "@/enums"
import { api } from "@/api"

export const actions: Actions<AuthActions, AuthState> = {
  async login({ commit }, payload: TLoginForm): Promise<boolean> {
    try {
      const googleTenantId = await getGoogleTenantId(payload.tenant)

      if (!googleTenantId) return false

      const auth = getAuth()
      const email = payload.email
      const password = payload.password
      auth.tenantId = googleTenantId
      const userCredential = await signInWithEmailAndPassword(
        auth,
        email,
        password
      )
      const user = userCredential.user

      if (user) {
        localStorage.setItem(LocalStorage.tenantId, payload.tenant)
        await saveUserWithTokenToStore(user)

        return true
      } else {
        throw new Error("Login failed")
      }
    } catch (error: any) {
      if (
        error.code === "auth/user-not-found" ||
        error.code === "auth/wrong-password" ||
        error.code === "auth/invalid-tenant-id"
      ) {
        alerts.addErrorAlert(
          "login-not-successful",
          i18n.t("login.failed") as string
        )
      } else if (error.code === "auth/user-disabled") {
        alerts.addErrorAlert(
          "login-not-successful",
          i18n.t("login.userDisabled") as string
        )
      } else {
        alerts.addErrorAlert(
          "init-user-not-successful",
          i18n.t("error.errorOccurredTryItLater") as string
        )
      }

      return false
    }
  },
  async updateSettings({ commit }, payload: UpdateUserInfo): Promise<boolean> {
    try {
      const auth = getAuth()
      const user = auth.currentUser
      if (!user) throw new Error(i18n.t("auth.userNotFound") as string)

      await updateProfile(user, {
        displayName: payload.displayName,
      })

      commit(
        AuthMutations.SetUser,
        transformUser({ ...user, displayName: payload.displayName })
      )

      return true
    } catch (error: any) {
      alerts.addErrorAlert("update-user-failed", error.message as string)

      return false
    }
  },
  async changePassword({ commit }, payload: string): Promise<boolean> {
    const auth = getAuth()

    const user = auth.currentUser

    try {
      await updatePassword(user as User, payload)
      return true
    } catch (error: any) {
      if (error.code === "auth/invalid-password") {
        alerts.addErrorAlert(
          "invalid-password",
          i18n.t("password.changeFailed") as string
        )
      } else if (error.code === "auth/weak-password") {
        alerts.addErrorAlert(
          "weak-password",
          i18n.t("password.weakPassword") as string
        )
      } else {
        alerts.addErrorAlert(
          "init-user-not-successful",
          i18n.t("error.errorOccurredTryItLater") as string
        )
      }

      return false
    }
  },
  async reauthenticate({ commit }, password: string) {
    try {
      const auth = getAuth()
      const user = auth.currentUser

      if (!user) throw new Error(i18n.t("auth.userNotFound") as string)

      auth.tenantId = user.tenantId
      await signInWithEmailAndPassword(auth, user.email as string, password)

      return true
    } catch (error: any) {
      if (error.code === "auth/wrong-password") {
        alerts.addErrorAlert(
          "wrong-password",
          i18n.t("login.wrongPassword") as string
        )
      } else
        alerts.addErrorAlert("reauthenticate-failed", error.message as string)

      return false
    }
  },
  async sendResetPasswordLink(
    { commit },
    payload: TResetPasswordForm
  ): Promise<boolean> {
    try {
      const googleTenantId = await getGoogleTenantId(payload.tenantId)

      if (!googleTenantId) return false

      const auth = getAuth()
      auth.tenantId = googleTenantId

      await sendPasswordResetEmail(auth, payload.email)

      alerts.addSuccessAlert(
        "reset-password-sent",
        i18n.t("message.resetPasswordLinkSent") as string
      )

      return true
    } catch (error: any) {
      if (error.code === "auth/user-not-found") {
        alerts.addErrorAlert(
          "reset-password-failed",
          i18n.t("auth.userNotFound") as string
        )
      } else if (error.code === "auth/invalid-tenant-id") {
        alerts.addErrorAlert(
          "reset-password-failed",
          i18n.t("auth.tenantNotFound") as string
        )
      } else
        alerts.addErrorAlert("reset-password-failed", error.message as string)

      console.error(error.code, error)
      return false
    }
  },
  async logout({ commit }) {
    try {
      const auth = getAuth()
      await signOut(auth)
      commit(AuthMutations.LogoutUser)
      await router.push({ name: Routes.Login })
    } catch (error: any) {
      alerts.addErrorAlert("logout-failed", error.message as string)
      console.error(error.code, error)
    }
  },
}

async function getGoogleTenantId(tenantId: string): Promise<string | null> {
  try {
    return await callApi(api.users.getTenantId, {
      tenantId: tenantId,
    })
  } catch (e) {
    return null
  }
}
