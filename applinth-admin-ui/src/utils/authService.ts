import { Routes } from "../enums"
import store from "../store"
import { router } from "./router"
import { AuthMutations, authNamespace } from "@/store/modules/auth"
import { transformUser } from "@/firebase"
import { assignTokenToApiCall } from "@/utils/apiClient"
import { User } from "firebase/auth"

export interface AuthenticationData {
  accessToken: string
  expiresIn: number
}

export function invalidateAuthentication() {
  store.commit("resetStore")
  router.push({ name: Routes.Login })
}

export async function saveUserWithTokenToStore(user: User): Promise<void> {
  const token = await user.getIdToken()

  store.commit(`${authNamespace}/${AuthMutations.SetUser}`, transformUser(user))
  store.commit(`${authNamespace}/${AuthMutations.SetAccessToken}`, token)

  assignTokenToApiCall(token)
}
