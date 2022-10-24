import { initializeApp } from "firebase/app"
import {
  getAuth,
  onAuthStateChanged,
  User as FirebaseUser,
} from "firebase/auth"
import { User } from "./store/modules/auth"
import { alerts, i18n, saveUserWithTokenToStore } from "@/utils"
import { config } from "@/config"
import { LocalStorage } from "@/enums"

const firebaseConfig = {
  apiKey: config.firebase.apiKey,
  authDomain: config.firebase.authDomain,
}

export function transformUser(user: FirebaseUser | null): User | null {
  if (!user) return user

  return {
    id: user.uid,
    name: user.displayName,
    email: user.email,
    googleTenantId: user.tenantId,
    tenantId: localStorage.getItem(LocalStorage.tenantId),
  }
}

export function initializeFirebaseAuth(initVue: () => void) {
  initializeApp(firebaseConfig)
  const auth = getAuth()
  onAuthStateChanged(auth, async (user) => {
    try {
      if (user) {
        await saveUserWithTokenToStore(user)
      }
    } catch (error: any) {
      alerts.addErrorAlert(
        "init-user-not-successful",
        i18n.t("error.errorOccurredTryItLater") as string
      )
    }

    initVue()
  })
}
