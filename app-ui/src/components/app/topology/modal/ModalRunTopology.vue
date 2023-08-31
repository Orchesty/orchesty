<template>
  <modal-template v-model="isOpen" :title="$t('topologies.run.title')" :on-confirm="submit" :on-close="reset">
    <template #default>
      <v-row dense>
        <v-col cols="12">
          <v-row dense>
            <v-col cols="12">
              <div>
                Topology: <span class="font-weight-medium">{{ topologyName }}</span>
              </div>
              <div>{{ $t('topologies.run.selectStartingPoint') }}</div>
            </v-col>
          </v-row>

          <v-row dense>
            <v-col cols="12">
              <v-list dense>
                <ValidationObserver ref="form" tag="form" @submit.prevent="submit">
                  <validation-provider v-slot="{ errors }" rules="required">
                    <v-list-item-group v-model="selected" multiple class="mb-2">
                      <v-list-item
                        v-for="item in startingPoints"
                        :key="item._id"
                        dense
                        class="d-flex justify-space-between align-center"
                      >
                        <template #default="{ active }">
                          <v-list-item-action class="mr-2">
                            <v-checkbox :color="!!errors[0] ? 'error' : 'primary'" :input-value="active" />
                          </v-list-item-action>
                          <v-list-item-title :class="{ 'error--text': !!errors[0] }" v-text="item.name" />
                        </template>
                      </v-list-item>
                    </v-list-item-group>
                    <span v-if="errors[0]" class="error--text"> Please select one of the starting points </span>
                  </validation-provider>
                </ValidationObserver>
              </v-list>
            </v-col>
          </v-row>

          <v-row dense>
            <v-col cols="12">
              <div class="pb-1">{{ $t('topologies.run.bodyParameters') }}</div>
              <v-textarea v-model="body" outlined dense placeholder="{ 'FORMAT': 'JSON' }" />
            </v-col>
          </v-row>
        </v-col>
      </v-row>
    </template>
    <template #sendingButton>
      <v-row dense>
        <v-col cols="12" class="d-flex justify-end">
          <app-button
            :sending-title="$t('button.sending.run')"
            :is-sending="state.isSending"
            :button-title="$t('button.run')"
            :on-click="submit"
            :flat="false"
          />
        </v-col>
      </v-row>
    </template>
  </modal-template>
</template>

<script>
import { mapActions, mapGetters } from 'vuex'
import { REQUESTS_STATE } from '../../../../store/modules/api/types'
import { API } from '../../../../api'
import ModalTemplate from '@/components/commons/modal/ModalTemplate'
import { TOPOLOGIES } from '@/store/modules/topologies/types'
import { EVENTS, events } from '@/services/utils/events'
import AppButton from '@/components/commons/button/AppButton'

export default {
  name: 'ModalRunTopology',
  components: { AppButton, ModalTemplate },
  data() {
    return {
      isOpen: false,
      body: '{}',
      selected: [],
      selectedMemory: [],
      selectedTopologyMemory: null,
      selectedTopology: null,
      nodes: [],
    }
  },
  computed: {
    ...mapGetters(REQUESTS_STATE.NAMESPACE, [REQUESTS_STATE.GETTERS.GET_STATE]),
    state() {
      return this[REQUESTS_STATE.GETTERS.GET_STATE]([API.topology.run.id])
    },
    updateSelect() {
      return this.selected.map((index) => {
        return this.startingPoints[index]._id
      })
    },
    nodeItems() {
      return this.nodes.items
    },
    startingPoints() {
      if (this.nodeItems) {
        return this.nodeItems.filter((node) => ['start', 'cron', 'webhook'].includes(node.type))
      } else {
        return []
      }
    },
    requestBody() {
      return {
        body: this.body,
        startingPoints: this.updateSelect,
        topologyID: this.selectedTopology._id,
      }
    },
    topologyName() {
      return `${this.selectedTopology?.name} v.${this.selectedTopology?.version}`
    },
  },
  props: {
    disabled: {
      type: Boolean,
      default: false,
      required: false,
    },
  },
  mounted() {
    events.listen(EVENTS.MODAL.TOPOLOGY.RUN, async (topology) => {
      if (this.selectedMemory && this.selectedTopologyMemory?._id === topology._id) {
        this.selected = this.selectedMemory
      }
      this.selectedTopology = topology
      this.selectedTopologyMemory = topology
      this.nodes = await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES](this.selectedTopology._id)
      this.setStartingPoints()
      this.loadRunSettings()
      this.isOpen = true
    })
  },
  beforeDestroy() {
    events.remove(EVENTS.MODAL.TOPOLOGY.RUN)
  },
  methods: {
    ...mapActions(TOPOLOGIES.NAMESPACE, [TOPOLOGIES.ACTIONS.TOPOLOGY.RUN, TOPOLOGIES.ACTIONS.TOPOLOGY.RETURN_NODES]),
    reset() {
      this.selectedMemory = this.selected
      this.selected = []
      this.body = '{}'
      this.nodes = {}
      this.selectedTopology = {}
    },
    setStartingPoints() {
      if (this.startingPoints.length === 1) {
        this.selected = [0]
      }
    },
    saveRunSettings() {
      localStorage.setItem(
        'orchesty:runSettings',
        JSON.stringify({ id: this.selectedTopology._id, settings: this.body })
      )
    },
    loadRunSettings() {
      const runSettings = JSON.parse(localStorage.getItem('orchesty:runSettings'))
      if (runSettings) {
        if (runSettings.id === this.selectedTopology._id) {
          this.body = runSettings.settings
        }
      }
    },
    async submit() {
      const isOk = await this.$refs.form.validate()

      if (!isOk) {
        return
      }

      await this[TOPOLOGIES.ACTIONS.TOPOLOGY.RUN](this.requestBody).then(async (res) => {
        if (res) {
          this.saveRunSettings()
          this.isOpen = false
        }
      })
    },
  },
}
</script>
