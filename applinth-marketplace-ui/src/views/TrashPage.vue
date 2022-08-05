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
              fetch-on-init
              @select="onSelect"
            >
              <template #default="{ items }">
                <td @click="onRowClick(items)">
                  {{ items.item.topologyName }}
                </td>
                <td @click="onRowClick(items)">
                  {{ toLocalDateTime(items.item.created) }}
                </td>
              </template>
              <template v-if="gridHasSelection" #actions>
                <base-button
                  class="mr-2"
                  :button-title="$t('button.approve')"
                  :min-width="50"
                  :height="24"
                  :on-click="itemsAcceptAll"
                />
                <base-button
                  :button-title="$t('button.deny')"
                  color="secondary"
                  :min-width="50"
                  :height="24"
                  outlined
                  :on-click="itemsRejectAll"
                />
              </template>
            </data-grid-selectable>
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="12" md="7">
        <router-view />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import FlashMessageMixin from '../mixins/FlashMessageMixin'

import { GRIDS } from '@/utils/gridsConfig'
import {
  toLocalDateTime,
  toLocalTime,
} from '@/localization/filters/dateFilters'
import BaseButton from '@/components/commons/BaseButton'
import DataGridSelectable from '@/components/commons/DataGridSelectable'
import { ROUTES } from '@/router/routes'
import Heading from '@/components/commons/Heading'
import { redirectTo } from '@/utils/redirect'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'

export default {
  name: 'TrashPage',
  components: { Heading, DataGridSelectable, BaseButton },
  mixins: [FlashMessageMixin],
  data() {
    return {
      headers: [
        {
          text: 'grid.trash.header.topologyName',
          value: 'topologyName',
          align: 'start',
          sortable: true,
        },
        {
          text: 'grid.trash.header.created',
          value: 'created',
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
    getSelectedIds() {
      return this.selectedItems.map((item) => item.id)
    },
  },
  methods: {
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
        params: { ids: this.getSelectedIds },
      })
      await this.$refs.gridTrash.gridFetch()
    },
    async itemsRejectAll() {
      await callApi({
        requestData: API.trash.rejectAll,
        params: { ids: this.getSelectedIds },
      })
      await this.$refs.gridTrash.gridFetch()
    },
    onSelect(selectedItems) {
      this.gridHasSelection = !!selectedItems.length
      this.selectedItems = selectedItems
    },
  },
}
</script>
