<template>
  <v-container class="px-0" fluid>
    <v-row>
      <v-col cols="3">
        <v-text-field v-model="topologyName" hide-details dense outlined clearable label="Topology" />
      </v-col>
      <v-col cols="3">
        <v-text-field v-model="nodeName" hide-details dense outlined clearable label="Node" />
      </v-col>
      <v-col cols="3">
        <v-text-field
          v-model="native"
          hide-details
          dense
          outlined
          clearable
          label="Custom"
          placeholder="{'key':'value'}"
        />
      </v-col>
      <v-col cols="3">
        <v-btn text @click="filter">Filter</v-btn>
        <v-btn color="primary" class="ml-2" icon @click="reload()">
          <v-icon> mdi-reload </v-icon>
        </v-btn>
      </v-col>
    </v-row>
    <v-row>
      <v-col cols="12">
        <trash-grid ref="grid" :filter="{ topologyName, nodeName }" :native="native" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import TrashGrid from '@/components/app/trash/grid/TrashGrid'
export default {
  name: 'TrashHandler',
  components: { TrashGrid },
  data() {
    return {
      topologyName: '',
      nodeName: '',
      native: '',
    }
  },
  methods: {
    filter() {
      this.$refs.grid.sendFilter()
    },
    async reload() {
      await this.$refs.grid.$refs.grid.refresh()
    },
  },
}
</script>
