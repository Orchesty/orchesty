<template>
  <v-col cols="12" md="8">
    <div class="headline d-flex my-auto align-baseline">
      <span class="font-weight-bold truncate" style="max-width: 100%">{{ topology.name }}&nbsp;</span>
    </div>
  </v-col>
</template>

<script>
export default {
  name: 'TopologyTitle',
  props: {
    topology: {
      type: Object,
      required: true,
    },
  },
}
</script>

<style scoped></style>
