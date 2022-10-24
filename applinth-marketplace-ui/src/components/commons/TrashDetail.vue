<template>
  <div v-if="trash" class="pt-8">
    <v-row class="justify-end">
      <v-col>
        <v-row>
          <v-col>
            <sub-heading>{{ trash[columnTopologyKey] }}</sub-heading>
          </v-col>
        </v-row>
        <v-row>
          <v-col>
            <span>
              {{ toLocalDateTime(trash[columnDateKey]) }}
            </span>
          </v-col>
        </v-row>
      </v-col>
      <v-col cols="auto" class="d-flex justify-end">
        <actions-wrapper>
          <trash-approve-modal
            :trash-item="trash"
            @taskSubmitted="onTrashSubmitted"
          />
          <trash-update-modal
            :trash-item="trash"
            @refreshItemData="refreshItemData"
          />
          <trash-reject-modal
            :trash-item="trash"
            @taskSubmitted="onTrashSubmitted"
          />
        </actions-wrapper>
      </v-col>
    </v-row>

    <v-row>
      <v-col>
        <span>
          {{ trash.message.headers["result-message"] }}
        </span>
      </v-col>
    </v-row>
    <v-row>
      <v-col>
        <json-editor v-model="messageBody" is-readonly mode="view" />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import SubHeading from "@/components/commons/SubHeading"
import { callApi } from "@/utils/apiFetch"
import { API } from "@/api"
import { toLocalDateTime } from "@/localization/filters/dateFilters"
import TrashUpdateModal from "@/components/commons/TrashUpdateModal"
import JsonEditor from "@/components/commons/JsonEditor"
import TrashApproveModal from "@/components/commons/TrashAcceptModal"
import TrashRejectModal from "@/components/commons/TrashRejectModal"
import ActionsWrapper from "@/components/commons/ActionsWrapper"
import { COLUMN_TOPOLOGY_KEY, COLUMN_DATE_KEY } from "@/store/trash/types"

export default {
  name: "TrashDetail",
  components: {
    ActionsWrapper,
    TrashRejectModal,
    TrashApproveModal,
    JsonEditor,
    TrashUpdateModal,
    SubHeading,
  },
  data() {
    return {
      trash: null,
      toLocalDateTime,
      columnTopologyKey: COLUMN_TOPOLOGY_KEY,
      columnDateKey: COLUMN_DATE_KEY,
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
    onTrashSubmitted() {
      this.$emit("taskSubmitted")
    },
    async refreshItemData() {
      this.trash = await callApi({
        requestData: API.trash.getById,
        params: { id: this.$route.params.id },
      })
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
