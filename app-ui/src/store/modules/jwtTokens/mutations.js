import { JWT_TOKENS } from "@/store/modules/jwtTokens/types"

export default {
  [JWT_TOKENS.MUTATIONS.SET_TOKENS]: (state, data) => {
    state.items = data
  },
}
