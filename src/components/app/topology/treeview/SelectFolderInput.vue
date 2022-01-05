<template>
  <v-treeview
    v-if="topologies.length"
    :items="categoriesOnly"
    dense
    hoverable
    activatable
    return-object
    open-all
    :active.sync="parentFolder"
    @update:active="onActive"
  >
    <template #prepend="{ open }">
      <v-icon color="secondary">
        {{ open ? 'mdi-folder-open' : 'mdi-folder' }}
      </v-icon>
    </template>
    <template slot="label" slot-scope="{ item }">
      <div :class="`body-1 ${item.id === topology.category ? 'error--text' : 'primary--text'}`">
        {{ item.name }} {{ item.id === topology.category ? '(current)' : '' }}
      </div>
    </template>
  </v-treeview>
</template>

<script>
import { CATEGORY } from '../../../../store/modules/topologies/utils'
import { TOPOLOGY_TREE } from '@/enums'
import { TOPOLOGY_ENUMS } from '@/enums/topologyEnums'

export default {
  name: 'SelectFolderInput',
  data() {
    return {
      parentFolder: [],
      TOPOLOGY_TREE,
      PAGE_TABS_ENUMS: TOPOLOGY_ENUMS,
    }
  },
  props: {
    topologies: {
      type: Array,
      required: true,
    },
    topology: {
      type: Object,
      required: true,
    },
    value: {
      type: String,
      required: false,
      default: () => '',
    },
  },
  computed: {
    categoriesOnly() {
      if (!this.topologies.length) return []

      const reduce = (items) => {
        const categories = items.filter((item) => item.type === CATEGORY)

        categories.forEach((category) => {
          category.children = reduce(category.children)
          if (category.id === this.topology.category) {
            this.parentFolder = [{ id: category.id }]
          }
        })

        return categories
      }

      let categoriesTree = reduce(this.topologies)
      categoriesTree.push({ children: [], id: null, name: 'root', parent: null, type: 'CATEGORY' })
      return categoriesTree
    },
  },
  methods: {
    onActive(value) {
      if (value[0]?.id) {
        this.$emit('input', value[0].id)
      } else {
        this.$emit('input', null)
      }
    },
  },
}
</script>

<style>
.v-treeview-node__root {
  cursor: pointer !important;
}
</style>
