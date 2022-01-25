<template>
  <v-row dense>
    <v-col cols="12" lg="6">
      <health-check-queue />
    </v-col>
  </v-row>
</template>

<script>
import HealthCheckQueue from '@/components/app/healthCheck/grid/HealthCheckQueue'
export default {
  name: 'HealthCheckContainer',
  components: { HealthCheckQueue },
}
</script>

<style scoped></style>
