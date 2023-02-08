<template>
  <v-menu bottom left>
    <template #activator="{ on, attrs }">
      <BaseButton icon :on="on" :attrs="attrs" :disabled="disabled">
        <template #icon>
          <BaseIcon> mdi-dots-vertical </BaseIcon>
        </template>
      </BaseButton>
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
import AppListItem from "@/components/commons/AppListItem.vue"
import BaseButton from "@/components/commons/BaseButton"
import { callCustomApi } from "@/utils/apiFetch"
import BaseIcon from "@/components/commons/icon/BaseIcon.vue"
import showFlashMessage from "@/utils/flashMessage"
import { i18n } from "@/localization"
import { FLASH_MESSAGES_TYPES } from "@/store/flashMessages/types"

export default {
  name: "CustomActionsMenu",
  components: {
    AppListItem,
    BaseIcon,
    BaseButton,
  },
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
    async onClick(action) {
      if (action.action === "open") {
        window.open(action.url, "_blank", "noreferrer")
      } else if (action.action === "call") {
        const res = await callCustomApi({
          method: "post",
          body: { body: action.body || "[]" },
          url: action.url,
        })

        if (res) {
          if (res.started) {
            showFlashMessage(
              i18n.t("flashMessage.actionCallSuccess"),
              FLASH_MESSAGES_TYPES.SUCCESS
            )
          } else {
            showFlashMessage(res.message, FLASH_MESSAGES_TYPES.ERROR)
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
