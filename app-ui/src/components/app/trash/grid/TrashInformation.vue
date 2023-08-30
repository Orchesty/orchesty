<template>
  <div v-if="selected.length !== 1">
    <v-col cols="12">
      <v-row>
        <v-col>
          <h3 class="mb-3">{{ $t('topologies.userTask.information.selectedNodes') }}</h3>
          <template v-for="node in selected">
            <v-chip :key="node.topologyId" class="mr-spaced mb-2" outlined label>
              {{ node.nodeId }}
            </v-chip>
          </template>
          <h4 class="subtitle-2 my-3">
            {{ $t('topologies.userTask.information.amount') }}: {{ `${selected.length}` }}
          </h4>
          <v-btn :disabled="selected.length <= 0" color="primary" class="white--text mr-2 fixed-width">
            <span class="font-weight-black">{{ $t('topologies.userTask.buttons.approve') }}</span>
          </v-btn>
          <v-btn :disabled="selected.length <= 0" class="white--text fixed-width">
            <span>{{ $t('topologies.userTask.buttons.deny') }}</span>
          </v-btn>
        </v-col>
      </v-row>
    </v-col>
  </div>
  <div v-else>
    <v-col cols="12" class="d-flex flex-column">
      <v-row class="flex-grow-0 flex-shrink-1">
        <v-col cols="12" md="3">
          <h3>Node</h3>
          <div class="body-2 text-uppercase">{{ $t('topologies.userTask.information.nodeName') }}</div>
          <div class="body-2 truncate_header">{{ item ? item.id : '' }}</div>
        </v-col>
        <v-col cols="12" md="9">
          <div class="d-flex text-center justify-space-around height-100">
            <div class="flex-item">
              <h5>{{ $t('topologies.userTask.information.created') }}</h5>
              <span class="body-2">{{ item ? $options.filters.toLocalDate(item.created) : '' }}</span>
            </div>
            <div class="flex-item">
              <h5>{{ $t('topologies.userTask.information.parentID') }}</h5>
              <span class="body-2 px-1">{{ item ? item.processId : '' }}</span>
            </div>
            <div class="flex-item">
              <h5>{{ $t('topologies.userTask.information.topologyName') }}</h5>
              <span class="body-2 px-1">{{ item ? item.processId : '' }}</span>
            </div>
            <div class="flex-item">
              <h5>{{ $t('topologies.userTask.information.nodeName') }}</h5>
              <span class="body-2 px-1">{{ item ? item.processId : '' }}</span>
            </div>
            <div class="flex-item">
              <h5>{{ $t('topologies.userTask.information.processID') }}</h5>
              <span class="body-2 px-1">{{ item ? item.processId : '' }}</span>
            </div>
          </div>
        </v-col>
      </v-row>
      <v-row class="flex-grow-0 flex-shrink-1">
        <v-col class="py-0">
          <v-btn :disabled="selected.length <= 0" color="primary" class="white--text mr-2 fixed-width">
            <span class="font-weight-black">{{ $t('topologies.userTask.buttons.approve') }}</span>
          </v-btn>
          <v-btn :disabled="selected.length !== 1" color="secondary" class="white--text mr-2 fixed-width">
            <span class="font-weight-black">{{ $t('topologies.userTask.buttons.update') }}</span>
          </v-btn>
          <v-btn :disabled="selected.length <= 0" color="error" class="white--text fixed-width">
            <span class="font-weight-black">{{ $t('topologies.userTask.buttons.deny') }}</span>
          </v-btn>
        </v-col>
      </v-row>
      <v-row>
        <v-col class="pb-0">
          <v-expansion-panels v-model="panel" multiple>
            <v-expansion-panel class="mb-2">
              <v-expansion-panel-header>{{ $t('topologies.userTask.information.headers') }}</v-expansion-panel-header>
              <v-expansion-panel-content>
                Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the
                industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and
                scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap
                into electronic typesetting, remaining essentially unchanged.
              </v-expansion-panel-content>
            </v-expansion-panel>
            <v-expansion-panel class="mb-2">
              <v-expansion-panel-header>{{ $t('topologies.userTask.information.processID') }}</v-expansion-panel-header>
              <v-expansion-panel-content>{{ item ? item.processId : '' }}</v-expansion-panel-content>
            </v-expansion-panel>
            <v-expansion-panel>
              <v-expansion-panel-header>{{ $t('topologies.userTask.information.auditLog') }}</v-expansion-panel-header>
              <v-expansion-panel-content>{{ item ? item.auditLogs : '' }}</v-expansion-panel-content>
            </v-expansion-panel>
          </v-expansion-panels>
        </v-col>
      </v-row>
    </v-col>
  </div>
</template>

<script>
import { toLocalDate } from '@/services/utils/dateFilters'

export default {
  name: 'TrashInformation',
  data() {
    return {
      panel: [0, 1, 2],
    }
  },
  props: {
    item: {
      type: Object,
      required: false,
      default: () => {},
    },
    selected: {
      type: Array,
      required: false,
      default: () => [],
    },
  },
  filters: {
    toLocalDate,
  },
}
</script>

<style scoped lang="scss">
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
