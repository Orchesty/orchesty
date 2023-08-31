<template>
  <v-row dense>
    <v-col cols="12">
      <trash-grid />
    </v-col>
  </v-row>
</template>

<script>
import TrashGrid from "@/components/app/trash/grid/TrashGrid"
export default {
  name: "TrashHandler",
  components: { TrashGrid },
}
</script>
