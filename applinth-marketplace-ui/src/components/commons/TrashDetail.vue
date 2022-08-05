<template>
  <div v-if="trash" class="pt-8">
    <v-row class="justify-end">
      <v-col>
        <v-row>
          <v-col>
            <sub-heading>{{ trash.topologyName }}</sub-heading>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <span>
              {{ toLocalDate(trash.created) }}
              {{ toLocalTime(trash.created) }}
            </span>
          </v-col>
        </v-row>
      </v-col>
      <v-col class="d-flex justify-end">
        <base-button
          :button-title="$t('button.approve')"
          class="mr-2"
          :on-click="itemAccept"
        />
        <base-button
          :button-title="$t('button.deny')"
          outlined
          color="secondary"
          class="mr-2"
          :on-click="itemReject"
        />
        <trash-update-modal
          :trash-item="trash"
          @refreshItemData="refreshItemData"
        />
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <span>
          {{ trash.message.headers['result-message'] }}
        </span>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <json-editor v-model="messageBody" mode="view" />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import SubHeading from '@/components/commons/SubHeading'
import { callApi } from '@/utils/apiFetch'
import { API } from '@/api'
import { toLocalDate, toLocalTime } from '@/localization/filters/dateFilters'
import BaseButton from '@/components/commons/BaseButton'
import { ROUTES } from '@/router/routes'
import TrashUpdateModal from '@/components/commons/TrashUpdateModal'
import JsonEditor from '@/components/commons/JsonEditor'

export default {
  name: 'TrashDetail',
  components: {
    JsonEditor,
    TrashUpdateModal,
    BaseButton,
    SubHeading,
  },
  data() {
    return {
      trash: null,
      toLocalDate,
      toLocalTime,
    }
  },
  computed: {
    messageBody: {
      get() {
        return JSON.parse(this.trash.message.body)
      },
      set(body) {
        this.trash.message.body = JSON.stringify(body)
      },
    },
  },
  methods: {
    async refreshItemData() {
      this.trash = await callApi({
        requestData: API.trash.getById,
        params: { id: this.$route.params.id },
      })
    },
    async itemAccept() {
      await callApi({
        requestData: API.trash.accept,
        params: { id: this.$route.params.id },
      })
      await this.$router.push({ name: ROUTES.TRASH })
    },
    async itemReject() {
      await callApi({
        requestData: API.trash.reject,
        params: { id: this.$route.params.id },
      })
      await this.$router.push({ name: ROUTES.TRASH })
    },
  },
  watch: {
    $route: {
      immediate: true,
      async handler(to) {
        this.trash = await callApi({
          requestData: API.trash.getById,
          params: { id: to.params.id },
        })
      },
    },
  },
}
</script>

<style scoped></style>
