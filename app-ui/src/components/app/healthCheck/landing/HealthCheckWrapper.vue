<template>
  <div>
    <v-row dense>
      <v-col cols="12" lg="6">
        <health-check-queue />
      </v-col>
      <v-col cols="12" lg="6">
        <health-check-container />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import HealthCheckQueue from '@/components/app/healthCheck/grid/HealthCheckQueue'
import HealthCheckContainer from '@/components/app/healthCheck/grid/HealthCheckContainer'
export default {
  name: 'HealthCheckWrapper',
  components: { HealthCheckContainer, HealthCheckQueue },
}
</script>

<style scoped></style>
