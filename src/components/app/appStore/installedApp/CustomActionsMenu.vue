<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <AppButton icon :on="on" :attrs="attrs" :disabled="disabled">
        <template #icon>
          <app-icon color="primary"> mdi-dots-vertical </app-icon>
        </template>
      </AppButton>
    </template>

    <v-list dense>
      <template v-for="(item, index) in customActions">
        <AppListItem
          :key="index"
          icon-color="primary"
          :text="item.name"
          span-class="min-width-80"
          icon=""
          :on-click="() => onClick(item)"
        />
      </template>
    </v-list>
  </v-menu>
</template>

<script>
import AppIcon from "@/components/commons/icon/AppIcon.vue"
import AppListItem from "@/components/commons/AppListItem.vue"
import AppButton from "@/components/commons/button/AppButton"
import { mapActions } from "vuex"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin.vue"

export default {
  name: "CustomActionsMenu",
  components: {
    AppListItem,
    AppIcon,
    AppButton,
  },
  mixins: [FlashMessageMixin],
  props: {
    disabled: {
      type: Boolean,
      required: true,
    },
    customActions: {
      type: Array,
      default: () => [],
    },
  },
  methods: {
    ...mapActions(REQUESTS_STATE.NAMESPACE, [
      REQUESTS_STATE.ACTIONS.CALL_CUSTOM_REQUEST,
    ]),
    async onClick(action) {
      if (action.action === "open") {
        window.open(action.url, "_blank", "noreferrer")
      } else if (action.action === "call") {
        const res = await this[REQUESTS_STATE.ACTIONS.CALL_CUSTOM_REQUEST]({
          method: "post",
          body: { body: action.body || "[]" },
          url: action.url,
        })

        if (res) {
          if (res.started === true) {
            this.showFlashMessage(
              false,
              this.$t("flashMessages.actionCallSuccess")
            )
          } else {
            this.showFlashMessage(true, res.message)
          }
        }
      } else {
        //
      }
    },
  },
}
</script>

<style scoped>
/deep/ .min-width-80 {
  min-width: 80px;
}
</style>
