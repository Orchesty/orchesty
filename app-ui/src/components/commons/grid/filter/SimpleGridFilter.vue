<template>
  <div>
    <user-task-grid-simple-filter v-if="simpleFilterEnum === SIMPLE_FILTER.USER_TASK" @fetchGrid="onFetchGrid" />
    <trash-grid-simple-filter v-if="simpleFilterEnum === SIMPLE_FILTER.TRASH" @fetchGrid="onFetchGrid" />
    <logs-grid-simple-filter v-if="simpleFilterEnum === SIMPLE_FILTER.LOGS" @fetchGrid="onFetchGrid" />
  </div>
</template>

<script>
import { SIMPLE_FILTER } from '@/services/enums/dataGridFilterEnums'
import UserTaskGridSimpleFilter from '@/components/commons/grid/filter/UserTaskGridSimpleFilter'
import TrashGridSimpleFilter from '@/components/commons/grid/filter/TrashGridSimpleFilter'
import LogsGridSimpleFilter from '@/components/commons/grid/filter/LogsGridSimpleFilter'

export default {
  name: 'SimpleGridFilter',
  components: {
    LogsGridSimpleFilter,
    TrashGridSimpleFilter,
    UserTaskGridSimpleFilter,
  },
  props: {
    simpleFilterEnum: {
      type: String,
      required: true,
    },
  },
  data() {
    return {
      SIMPLE_FILTER,
    }
  },
  methods: {
    onFetchGrid(params) {
      const search = params?.search || null
      const filter = params?.filter || []
      this.$emit('onSendFilter', { search, filter, paging: null, sorter: null })
    },
  },
}
</script>
