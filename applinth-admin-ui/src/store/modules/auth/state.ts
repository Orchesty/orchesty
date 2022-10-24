import { User } from "./types"

export interface AuthState {
  user: User | null
  accessToken: string | null
}

export const createState = (): AuthState => {
  return {
    user: null,
    accessToken: null,
  }
}
