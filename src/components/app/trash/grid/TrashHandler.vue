<template>
  <v-container class="px-0" fluid>
    <v-row>
      <v-col cols="12">
        <trash-grid ref="grid" />
      </v-col>
    </v-row>
  </v-container>
</template>

<script>
import TrashGrid from '@/components/app/trash/grid/TrashGrid'
export default {
  name: 'TrashHandler',
  components: { TrashGrid },
}
</script>
