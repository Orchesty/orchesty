<template>
  <div>
    <v-row>
      <v-col>
        <heading>{{ $t('trashPage.heading') }}</heading>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12" md="5">
        <v-row>
          <v-col>
            <data-grid-selectable
              ref="gridTrash"
              :headers="headers"
              :grid-settings="GRIDS.TRASH"
              :sort-by="[columnDateKey]"
              :sort-desc="[true]"
              @select="onSelect"
            >
              <template #default="{ items }">
                <td @click="onRowClick(items)">
                  {{ items.item[columnTopologyKey] }}
                </td>
                <td @click="onRowClick(items)">
                  {{ toLocalDateTime(items.item[columnDateKey]) }}
                </td>
              </template>
              <template v-if="gridHasSelection" #actions>
                <ActionsWrapper>
                  <TrashAcceptAllModal
                    :number-of-items="selectedItems.length"
                    @confirm="itemsAcceptAll"
                  />
                  <TrashRejectAllModal
                    :number-of-items="selectedItems.length"
                    @confirm="itemsRejectAll"
                  />
                </ActionsWrapper>
              </template>
            </data-grid-selectable>
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="12" md="7">
        <router-view @taskSubmitted="onTaskSubmitted" />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import { GRIDS } from '@/utils/gridsConfig'
import {
  toLocalDateTime,
  toLocalTime,
} from '@/localization/filters/dateFilters'
import DataGridSelectable from '@/components/commons/DataGridSelectable'
import { ROUTES } from '@/router/routes'
import Heading from '@/components/commons/Heading'
import { redirectTo } from '@/utils/redirect'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import showFlashMessage from '@/utils/flashMessage'
import { FLASH_MESSAGES_TYPES } from '@/store/flashMessages/types'
import ActionsWrapper from '@/components/commons/ActionsWrapper'
import TrashAcceptAllModal from '@/components/commons/TrashAcceptAllModal'
import TrashRejectAllModal from '@/components/commons/TrashRejectAllModal'
import { COLUMN_TOPOLOGY_KEY, COLUMN_DATE_KEY } from '@/store/trash/types'

export default {
  name: 'TrashPage',
  components: {
    ActionsWrapper,
    TrashRejectAllModal,
    TrashAcceptAllModal,
    Heading,
    DataGridSelectable,
  },
  data() {
    return {
      columnTopologyKey: COLUMN_TOPOLOGY_KEY,
      columnDateKey: COLUMN_DATE_KEY,
      headers: [
        {
          text: this.$t('grid.trash.header.topologyName'),
          value: this.columnTopologyKey,
          align: 'start',
          sortable: true,
        },
        {
          text: this.$t('grid.trash.header.updated'),
          value: this.columnDateKey,
          align: 'start',
          sortable: true,
        },
      ],
      GRIDS,
      toLocalDateTime,
      toLocalTime,
      redirectTo,
      gridHasSelection: false,
      selectedItems: null,
    }
  },
  computed: {
    selectedIds() {
      return this.selectedItems.map((item) => item.id)
    },
  },
  methods: {
    async onTaskSubmitted() {
      await this.$router.push({ name: ROUTES.TRASH })
      await this.$refs.gridTrash.gridFetch()
    },
    async onRowClick(items) {
      this.$refs.gridTrash.onRowClicked(items)
      await this.redirectTo(this.$router, {
        name: ROUTES.TRASH_DETAIL,
        params: { id: items.item.id },
      })
    },
    async itemsAcceptAll() {
      await callApi({
        requestData: API.trash.acceptAll,
        params: [...this.selectedIds],
      })
      await this.$refs.gridTrash.gridFetch()
      showFlashMessage(
        this.$t('flashMessage.acceptedList', {
          number: this.selectedIds.length,
        }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },
    async itemsRejectAll() {
      await callApi({
        requestData: API.trash.rejectAll,
        params: [...this.selectedIds],
      })
      await this.$refs.gridTrash.gridFetch()
      showFlashMessage(
        this.$t('flashMessage.rejectedList', {
          number: this.selectedIds.length,
        }),
        FLASH_MESSAGES_TYPES.SUCCESS
      )
    },
    onSelect(selectedItems) {
      this.gridHasSelection = !!selectedItems.length
      this.selectedItems = [...selectedItems]
    },
  },
}
</script>
