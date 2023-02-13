<template>
  <v-row class="px-5">
    <v-col
      v-if="item && (userTask || trash)"
      cols="12"
      class="d-flex flex-column"
    >
      <v-row class="flex-grow-0 flex-shrink-1" dense>
        <v-col v-if="isTrash" cols="6">
          <div>
            <span class="font-weight-bold"
              >{{ $t("page.status.topology") }}:
            </span>
            <span>{{ topologyNameWithVersion }}</span>
          </div>
          <div>
            <div class="my-3">
              <span class="font-weight-bold">{{ $t("page.status.id") }}: </span>
              <span>{{ trashTaskSwitcher("topologyId") }}</span>
            </div>
          </div>
        </v-col>
        <v-col :cols="isTrash ? 6 : 12">
          <div>
            <span class="font-weight-bold">{{ $t("page.status.node") }}: </span>
            <span> {{ trashTaskSwitcher("nodeName") }}</span>
          </div>
          <div class="my-3">
            <span class="font-weight-bold">{{ $t("page.status.id") }}: </span>
            <span>{{ trashTaskSwitcher("nodeId") }}</span>
          </div>
        </v-col>
        <v-col cols="auto">
          <v-row dense>
            <v-col cols="auto">
              <div>
                <span class="font-weight-bold">
                  {{ $t("page.status.created") }}
                </span>
              </div>
              <div>
                <span>{{
                  userTask || isTrash
                    ? $options.filters.internationalFormat(
                        trashTaskSwitcher("created")
                      )
                    : ""
                }}</span>
              </div>
            </v-col>
            <v-divider vertical class="mx-2" />
            <v-col cols="auto">
              <div>
                <span class="font-weight-bold">{{
                  $t("page.status.updated")
                }}</span>
              </div>
              <div>
                <span>{{
                  userTask || isTrash
                    ? $options.filters.internationalFormat(
                        trashTaskSwitcher("updated")
                      )
                    : ""
                }}</span>
              </div>
            </v-col>
            <v-divider vertical class="mx-2" />
            <v-col cols="auto">
              <div>
                <span class="font-weight-bold">
                  {{ $t("page.status.correlationId") }}
                </span>
              </div>
              <div>
                <span>{{ trashTaskSwitcher("correlationId") }}</span>
              </div>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
      <v-row class="flex-grow-0 flex-shrink-1" dense>
        <v-col>
          <TrashItemChangeTopologyModal
            v-if="isTrash && trash && trash.topologyDeleted === true"
            :on-submit="changeTopologyAndAccept"
          />
          <user-task-actions-modal
            v-else
            color="primary"
            :selected="selected"
            type="accept"
            :message="message"
            :on-submit="accept"
            @reset="reset"
          />
          <user-task-actions-modal
            color="primary"
            :selected="selected"
            type="update"
            :message="message"
            :on-submit="update"
          />
          <user-task-actions-modal
            color="error"
            :selected="selected"
            type="reject"
            :message="message"
            :on-submit="reject"
            @reset="reset"
          />
        </v-col>
      </v-row>
      <v-row dense>
        <v-col>
          <v-expansion-panels v-model="panel" flat multiple>
            <v-expansion-panel class="mb-2">
              <v-card outlined>
                <v-expansion-panel-header>
                  <span>
                    {{ $t("page.status.headers") }}
                  </span>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <vue-json-pretty
                    :path="'res'"
                    :data="trashTaskSwitcherMessage('headers')"
                  />
                </v-expansion-panel-content>
              </v-card>
            </v-expansion-panel>
            <v-expansion-panel class="mb-2">
              <v-card outlined>
                <v-expansion-panel-header>
                  <span>
                    {{ $t("page.status.body") }}
                  </span>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <vue-json-pretty
                    :path="'res'"
                    :data="trashTaskSwitcherMessage('body')"
                  />
                </v-expansion-panel-content>
              </v-card>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-col>
      </v-row>
    </v-col>
  </v-row>
</template>

<script>
import { internationalFormat } from "@/services/utils/dateFilters"
import { mapActions, mapState } from "vuex"
import UserTaskActionsModal from "@/components/app/userTasks/modal/UserTaskActionsModal"
import { USER_TASKS } from "@/store/modules/userTasks/types"
import "vue-json-pretty/lib/styles.css"
import VueJsonPretty from "vue-json-pretty"
import { TRASH } from "@/store/modules/trash/types"
import TrashItemChangeTopologyModal from "@/components/app/trash/modal/TrashItemChangeTopologyModal.vue"

export default {
  name: "UserTaskInformation",
  components: {
    TrashItemChangeTopologyModal,
    UserTaskActionsModal,
    VueJsonPretty,
  },
  data() {
    return {
      panel: [0, 1, 2],
    }
  },
  methods: {
    ...mapActions(USER_TASKS.NAMESPACE, [
      USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST,
      USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST,
      USER_TASKS.ACTIONS.USER_TASK_UPDATE,
      USER_TASKS.ACTIONS.USER_TASK_REJECT,
      USER_TASKS.ACTIONS.USER_TASK_ACCEPT,
      USER_TASKS.ACTIONS.USER_TASK_GET,
    ]),
    ...mapActions(TRASH.NAMESPACE, [
      TRASH.ACTIONS.TRASH_ACCEPT,
      TRASH.ACTIONS.TRASH_REJECT,
      TRASH.ACTIONS.TRASH_UPDATE,
      TRASH.ACTIONS.TRASH_TASK_GET,
    ]),
    trashTaskSwitcherMessage(param) {
      if (param === "body") {
        return this[this.toggler]
          ? this.parseBody(this[this.toggler].message[param])
          : {}
      } else {
        return this[this.toggler] ? this[this.toggler].message[param] : {}
      }
    },
    trashTaskSwitcher(param) {
      return this[this.toggler]?.[param]
    },
    async acceptAll() {
      const response = await this[USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]({
        ids: this.selected.map((item) => item.id),
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    async rejectAll() {
      const response = await this[USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]({
        ids: this.selected.map((item) => item.id),
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    async accept() {
      const response = await this[
        this.isTrash
          ? TRASH.ACTIONS.TRASH_ACCEPT
          : USER_TASKS.ACTIONS.USER_TASK_ACCEPT
      ]({
        id: this[this.toggler].id,
        topologyID: this[this.toggler].topologyId,
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    async changeTopologyAndAccept(data) {
      const response = await this[TRASH.ACTIONS.TRASH_ACCEPT]({
        id: this[this.toggler].id,
        data: {
          topologyId: data.topologyId,
          nodeId: data.nodeId,
        },
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    async update(val) {
      const response = await this[
        this.isTrash
          ? TRASH.ACTIONS.TRASH_UPDATE
          : USER_TASKS.ACTIONS.USER_TASK_UPDATE
      ]({
        id: this[this.toggler].id,
        ...val,
        topologyID: this[this.toggler].topologyId,
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    async reject() {
      const response = await this[
        this.isTrash
          ? TRASH.ACTIONS.TRASH_REJECT
          : USER_TASKS.ACTIONS.USER_TASK_REJECT
      ]({
        id: this[this.toggler].id,
        topologyID: this[this.toggler].topologyId,
      })
      if (response) {
        await this.fetchGrid()
      }
      return response
    },
    reset() {
      this.$emit("reset")
    },
    async fetchGrid() {
      await this.$emit("fetchGrid")
    },
    parseBody(body) {
      try {
        return JSON.parse(body)
      } catch (e) {
        if (body) {
          return body
        }

        return {}
      }
    },
  },
  computed: {
    ...mapState(USER_TASKS.NAMESPACE, ["userTask"]),
    ...mapState(TRASH.NAMESPACE, ["trash"]),
    toggler() {
      return this.isTrash ? "trash" : "userTask"
    },
    body() {
      return this[this.toggler]
        ? this.parseBody(this[this.toggler].message.body)
        : {}
    },
    message() {
      return this[this.toggler] ? this[this.toggler].message : {}
    },
    topologyNameWithVersion() {
      return `${this.trash?.topologyName} v.${this.trash?.topologyVersion}`
    },
  },
  props: {
    item: {
      type: Object,
      required: false,
      default: () => {},
    },
    isTrash: {
      type: Boolean,
      default: false,
    },
    selected: {
      type: Array,
      required: false,
      default: () => [],
    },
  },
  watch: {
    item: {
      deep: true,
      immediate: true,
      async handler() {
        if (this.item) {
          await this[
            this.isTrash
              ? TRASH.ACTIONS.TRASH_TASK_GET
              : USER_TASKS.ACTIONS.USER_TASK_GET
          ](this.item.id)
        }
      },
    },
    selected: {
      immediate: true,
      async handler() {
        if (this.selected[0]) {
          await this[
            this.isTrash
              ? TRASH.ACTIONS.TRASH_TASK_GET
              : USER_TASKS.ACTIONS.USER_TASK_GET
          ](this.selected[0].id)
        }
      },
    },
  },
  filters: {
    internationalFormat,
  },
}
</script>

<style scoped lang="scss">
.json-ready {
  white-space: pre-wrap;
}

.height-100 {
  height: 100%;
  flex-wrap: wrap;
}

.flex-item {
  display: flex;
  justify-content: space-around;
  flex: 1;
  min-width: 0;
  flex-direction: column;

  @media #{map-get($display-breakpoints, 'sm-and-down')} {
    flex: 1 0 100%;
    align-items: flex-start;
  }

  span {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
}

.fixed-width {
  min-width: 110px !important;
}

.mr-spaced:not(:last-child) {
  margin-right: 10px;
}
</style>
