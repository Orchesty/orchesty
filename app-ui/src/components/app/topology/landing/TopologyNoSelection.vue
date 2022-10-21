<template>
  <div class="topology-not-found-container">
    <h4 class="display-2 gray--text darken-1--text">{{ $t('page.status.noTopologySelected') }}</h4>
  </div>
</template>

<script>
export default {
  name: 'TopologyNoSelection',
}
</script>

<style scoped>
.topology-not-found-container {
  height: 100%;
  display: flex;
  justify-content: center;
  align-items: center;
}
</style>
