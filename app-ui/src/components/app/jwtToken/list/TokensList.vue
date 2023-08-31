<template>
  <DataGrid
    ref="grid"
    disable-filter
    :is-loading="state.isSending"
    :namespace="DATA_GRIDS.JWT_TOKENS"
    :headers="headers"
  >
    <template #default="{ items }">
      <td>{{ toLocalDate(items.item.created) }}</td>
      <td>{{ renderExpireAt(items.item) }}</td>
      <td>
        {{ items.item.scopes.join(", ") }}
      </td>
      <Tooltip>
        <template #activator="{ on, attrs }">
          <td v-bind="attrs" class="text-center" v-on="on">
            <AppButton icon :on-click="() => copyToClipboard(items.item.key)"
              ><template #icon
                ><AppIcon>mdi-content-copy</AppIcon></template
              ></AppButton
            >
          </td>
        </template>
        <template #tooltip>
          {{ items.item.key }}
        </template>
      </Tooltip>
      <td class="text-right">
        <AppButton icon :on-click="() => deleteItem(items.item)"
          ><template #icon><AppIcon>delete</AppIcon></template></AppButton
        >
      </td>
    </template>
  </DataGrid>
</template>

<script>
import { DATA_GRIDS } from "@/services/enums/dataGridEnums"
import { mapActions, mapGetters } from "vuex"
import { JWT_TOKENS } from "@/store/modules/jwtTokens/types"
import DataGrid from "@/components/commons/grid/DataGrid"
import { REQUESTS_STATE } from "@/store/modules/api/types"
import { API } from "@/api"
import { GRID } from "@/store/modules/grid/types"
import { toLocalDate } from "@/services/utils/dateFilters"
import { EVENTS, events } from "@/services/utils/events"
import Tooltip from "@/components/commons/Tooltip"
import AppButton from "@/components/commons/button/AppButton"
import FlashMessageMixin from "@/services/mixins/FlashMessageMixin"
import AppIcon from "@/components/commons/icon/AppIcon.vue"

export default {
  name: "TokensList",
  components: { AppIcon, AppButton, DataGrid, Tooltip },
  mixins: [FlashMessageMixin],
  computed: {
    ...mapGetters(DATA_GRIDS.JWT_TOKENS, {
      pagingInitial: GRID.GETTERS.GET_PAGING,
      sorterInitial: GRID.GETTERS.GET_SORTER,
    }),
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE](API.jwtTokens.grid.id)
    },
  },
  data() {
    return {
      DATA_GRIDS,
      headers: [
        {
          text: this.$t("grid.header.created"),
          value: "created",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: this.$t("grid.header.expireAt"),
          value: "expireAt",
          align: "left",
          sortable: true,
          visible: true,
        },
        {
          text: this.$t("grid.header.scopes"),
          value: "scopes",
          align: "left",
          sortable: false,
          visible: true,
        },
        {
          text: this.$t("grid.header.key"),
          value: "key",
          align: "center",
          sortable: false,
          visible: true,
          width: "90px",
        },
        {
          text: this.$t("grid.header.actions"),
          value: "actions",
          align: "right",
          sortable: false,
          visible: true,
          width: "90px",
        },
      ],
    }
  },
  methods: {
    ...mapActions(JWT_TOKENS.NAMESPACE, [
      JWT_TOKENS.ACTIONS.FETCH_LIST,
      JWT_TOKENS.ACTIONS.DELETE,
    ]),
    async fetchItems() {
      await this.$refs.grid.fetchGrid(
        null,
        null,
        null,
        this.pagingInitial,
        this.sorterInitial
      )
    },
    async deleteItem(item) {
      const result = await this[JWT_TOKENS.ACTIONS.DELETE]({ id: item.id })
      if (result) {
        await this.fetchItems()
      }
    },
    renderExpireAt(item) {
      if (item.expireAt) {
        return toLocalDate(item.expireAt)
      } else {
        return this.$t("page.text.never")
      }
    },
    copyToClipboard(key) {
      navigator.clipboard.writeText(key)
      this.showFlashMessage(false, this.$t("flashMessages.copied"))
    },
    toLocalDate: toLocalDate,
  },
  async mounted() {
    await this.$refs.grid.fetchGridWithInitials(
      null,
      null,
      null,
      this.pagingInitial,
      this.sorterInitial
    )
  },
  created() {
    events.listen(EVENTS.MODAL.JWT_TOKEN.CREATE, async () => {
      await this.fetchItems()
    })
  },
}
</script>
