import { createState } from "./state"
import { mutations } from "./mutations"

export * from "./types"
export * from "./state"

export const alertsModule = {
  namespaced: true,
  state: createState(),
  mutations,
}
