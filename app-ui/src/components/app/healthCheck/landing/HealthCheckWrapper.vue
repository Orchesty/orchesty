<template>
  <div>
    <v-row dense>
      <v-col cols="12">
        <HealthCheckErrors />
      </v-col>
    </v-row>
  </div>
</template>

<script>
import HealthCheckErrors from "@/components/app/healthCheck/grid/HealthCheckErrors"
export default {
  name: "HealthCheckWrapper",
  components: { HealthCheckErrors },
}
</script>

<style scoped></style>
