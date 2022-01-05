<template>
  <v-row>
    <v-col v-if="item && (userTask || trash)" cols="12" class="d-flex flex-column">
      <v-row class="flex-grow-0 flex-shrink-1">
        <v-col v-if="isTrash" cols="6" class="truncate_header">
          <h5 class="subtitle-2 font-weight-bold">Topology</h5>
          <span class="body-2 text-uppercase truncate">{{ trashTaskSwitcher('topologyName') }}</span>
          <div class="body-2">{{ trashTaskSwitcher('topologyId') }}</div>
        </v-col>
        <v-col :cols="isTrash ? 6 : 12" class="truncate_header">
          <h5 class="subtitle-2 font-weight-bold">Node</h5>
          <span class="body-2 text-uppercase">
            {{ trashTaskSwitcher('nodeName') }}
          </span>
          <div class="body-2">{{ trashTaskSwitcher('nodeId') }}</div>
        </v-col>
        <v-col cols="auto">
          <v-row>
            <v-col cols="auto">
              <h5 class="subtitle-2 font-weight-bold">{{ $t('topologies.userTask.information.created') }}</h5>
              <span class="body-2">{{
                userTask || isTrash ? $options.filters.internationalFormat(trashTaskSwitcher('created')) : ''
              }}</span>
            </v-col>
            <v-col cols="auto">
              <h5 class="subtitle-2 font-weight-bold">{{ $t('topologies.userTask.information.updated') }}</h5>
              <span class="body-2">{{
                userTask || isTrash ? $options.filters.internationalFormat(trashTaskSwitcher('updated')) : ''
              }}</span>
            </v-col>
            <v-col cols="auto">
              <h5 class="subtitle-2 font-weight-bold">{{ $t('topologies.userTask.information.correlationId') }}</h5>
              <span class="body-2">{{ trashTaskSwitcher('correlationId') }}</span>
            </v-col>
          </v-row>
        </v-col>
      </v-row>
      <v-row class="flex-grow-0 flex-shrink-1">
        <v-col class="py-0">
          <user-task-actions-modal
            color="primary"
            :selected="selected"
            type="accept"
            :data="message"
            :method="accept"
            @reset="reset"
          />
          <user-task-actions-modal
            color="primary"
            :selected="selected"
            type="update"
            :data="message"
            :method="update"
          />
          <user-task-actions-modal
            color="error"
            :selected="selected"
            type="reject"
            :data="message"
            :method="reject"
            @reset="reset"
          />
        </v-col>
      </v-row>
      <v-row>
        <v-col>
          <v-expansion-panels v-model="panel" flat multiple>
            <v-expansion-panel class="mb-2">
              <v-card outlined>
                <v-expansion-panel-header>
                  <h4>
                    {{ $t('topologies.userTask.information.headers') }}
                  </h4>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <vue-json-pretty :path="'res'" :data="trashTaskSwitcherMessage('headers')" />
                </v-expansion-panel-content>
              </v-card>
            </v-expansion-panel>
            <v-expansion-panel class="mb-2">
              <v-card outlined>
                <v-expansion-panel-header>
                  <h4>
                    {{ $t('topologies.userTask.information.body') }}
                  </h4>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <vue-json-pretty :path="'res'" :data="trashTaskSwitcherMessage('body')" />
                </v-expansion-panel-content>
              </v-card>
            </v-expansion-panel>
            <v-expansion-panel>
              <v-card outlined>
                <v-expansion-panel-header>
                  <h4>
                    {{ $t('topologies.userTask.information.auditLog') }}
                  </h4>
                </v-expansion-panel-header>
                <v-expansion-panel-content>
                  <vue-json-pretty :path="'res'" :data="trashTaskSwitcher('auditLogs')" />
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
import { internationalFormat } from '../../../../filters'
import { mapActions, mapState } from 'vuex'
import UserTaskActionsModal from '@/components/app/userTasks/modal/UserTaskActionsModal'
import { USER_TASKS } from '@/store/modules/userTasks/types'
import 'vue-json-pretty/lib/styles.css'
import VueJsonPretty from 'vue-json-pretty'
import { TRASH } from '@/store/modules/trash/types'

export default {
  name: 'UserTaskInformation',
  components: { UserTaskActionsModal, VueJsonPretty },
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
      if (param === 'body') {
        return this[this.toggler] ? JSON.parse(this[this.toggler].message[param]) : {}
      } else {
        return this[this.toggler] ? this[this.toggler].message[param] : {}
      }
    },
    trashTaskSwitcher(param) {
      return this[this.toggler][param]
    },
    async acceptAll() {
      return await this[USER_TASKS.ACTIONS.USER_TASK_ACCEPT_LIST]({ ids: this.selected.map((item) => item.id) })
    },
    async rejectAll() {
      return await this[USER_TASKS.ACTIONS.USER_TASK_REJECT_LIST]({ ids: this.selected.map((item) => item.id) })
    },
    async accept() {
      return await this[this.isTrash ? TRASH.ACTIONS.TRASH_ACCEPT : USER_TASKS.ACTIONS.USER_TASK_ACCEPT]({
        id: this[this.toggler].id,
        topologyID: this[this.toggler].topologyId,
      })
    },
    async update(val) {
      return await this[this.isTrash ? TRASH.ACTIONS.TRASH_UPDATE : USER_TASKS.ACTIONS.USER_TASK_UPDATE]({
        id: this[this.toggler].id,
        ...val,
        topologyID: this[this.toggler].topologyId,
      })
    },
    async reject() {
      return await this[this.isTrash ? TRASH.ACTIONS.TRASH_REJECT : USER_TASKS.ACTIONS.USER_TASK_REJECT]({
        id: this[this.toggler].id,
        topologyID: this[this.toggler].topologyId,
      })
    },
    reset() {
      this.$emit('reset')
    },
  },
  computed: {
    ...mapState(USER_TASKS.NAMESPACE, ['userTask']),
    ...mapState(TRASH.NAMESPACE, ['trash']),
    toggler() {
      return this.isTrash ? 'trash' : 'userTask'
    },
    body() {
      return this[this.toggler] ? JSON.parse(this[this.toggler].message.body) : {}
    },
    message() {
      return this[this.toggler] ? this[this.toggler].message : {}
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
          await this[this.isTrash ? TRASH.ACTIONS.TRASH_TASK_GET : USER_TASKS.ACTIONS.USER_TASK_GET](this.item.id)
        }
      },
    },
    selected: {
      immediate: true,
      async handler() {
        if (this.selected[0]) {
          await this[this.isTrash ? TRASH.ACTIONS.TRASH_TASK_GET : USER_TASKS.ACTIONS.USER_TASK_GET](
            this.selected[0].id
          )
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
.truncate_header {
  white-space: nowrap;
  text-overflow: ellipsis;
  overflow: hidden;
  max-width: 100%;
}
</style>
