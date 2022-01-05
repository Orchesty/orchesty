<template>
  <v-row>
    <v-col cols="auto" class="d-flex flex-wrap align-center flex-grow-1">
      <v-btn
        v-for="(item, index) in items"
        :key="index"
        small
        :outlined="!item.active"
        :color="item.active ? 'primary' : 'secondary'"
        class="mr-2"
        @click="onChangeFilter(index, item)"
      >
        {{ $t(item.name) }}
      </v-btn>
      <slot name="advancedFilter" />
      <slot name="buttonLeft" />
      <slot name="buttonRight" />
    </v-col>
  </v-row>
</template>

<script>
import { FILTER } from '../../../../store/grid'
import { mapActions, mapState } from 'vuex'
import { ADMIN_USERS } from '@/store/modules/adminUsers/types'
import { AUTH } from '@/store/modules/auth/types'

export default {
  name: 'QuickGridFilter',
  props: {
    quickFilters: {
      type: Array,
      required: true,
    },
    filter: {
      type: Array,
      required: true,
    },
    filterMeta: {
      type: Object,
      required: true,
    },
    onChange: {
      type: Function,
      required: true,
    },
    defaultSetting: {
      type: Object,
      required: false,
      default: () => ({}),
    },
  },
  data() {
    return {
      items: this.createItems(this.quickFilters, this.filterMeta.index || undefined),
    }
  },
  watch: {
    filterMeta(meta) {
      // reset filter if not QUICK_FILTER
      if (!meta || meta.type !== FILTER.QUICK_FILTER) {
        this.items = this.createItems(this.quickFilters, meta.index)
      }
    },
  },
  created() {
    if (this.quickFilters.length && this.isQuickFilter(this.filterMeta) && this.quickFilters[this.filterMeta.index]) {
      this.onChange(this.quickFilters[this.filterMeta.index].filter, {
        type: FILTER.QUICK_FILTER,
        index: this.filterMeta.index,
      })
    }
  },
  computed: {
    ...mapState(AUTH.NAMESPACE, ['user']),
  },
  methods: {
    ...mapActions(ADMIN_USERS.NAMESPACE, [ADMIN_USERS.ACTIONS.GET_USER_REQUEST]),
    onChangeFilter(index, filter) {
      this.items = this.createItems(this.items, index)

      let withDefault = filter.filter
      if (this.defaultSetting && this.defaultSetting.permanentFilter === true && this.defaultSetting.filter) {
        withDefault = [...filter.filter, ...this.defaultSetting.filter]
      }

      this.onChange(withDefault, { type: FILTER.QUICK_FILTER, index })
    },
    createItems(filters, index = undefined) {
      return filters.map((item, i) => {
        item.active = false

        if (index === i) {
          item.active = true
        }

        return item
      })
    },
    isQuickFilter(meta) {
      return meta.type && (meta.type === FILTER.QUICK_FILTER || meta.type === undefined)
    },
  },
}
</script>
