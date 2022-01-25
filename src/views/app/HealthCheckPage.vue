<template>
  <content-basic title="Health Check">
    <health-check-container />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import HealthCheckContainer from '@/components/app/healthCheck/landing/HealthCheckContainer'
export default {
  name: 'HealthCheckPage',
  components: { HealthCheckContainer, ContentBasic },
}
</script>

<style scoped></style>
