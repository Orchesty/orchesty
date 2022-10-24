<script>
import { mapActions } from "vuex"
import {
  FLASH_MESSAGES,
  FLASH_MESSAGES_TYPES,
} from "@/store/modules/flashMessages/types"

export default {
  name: "FlashMessageMixin",
  methods: {
    ...mapActions(FLASH_MESSAGES.NAMESPACE, [FLASH_MESSAGES.ACTIONS.ADD]),
    showFlashMessage(isError, message) {
      this[FLASH_MESSAGES.ACTIONS.ADD]({
        id: 0,
        message: message,
        type: FLASH_MESSAGES_TYPES[isError ? "ERROR" : "SUCCESS"],
      })
    },
  },
}
</script>

<style scoped></style>
