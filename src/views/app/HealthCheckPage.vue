<template>
  <content-basic :title="$t('navigation.healthCheck')">
    <health-check-wrapper />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import HealthCheckWrapper from '@/components/app/healthCheck/landing/HealthCheckWrapper'
export default {
  name: 'HealthCheckPage',
  components: { HealthCheckWrapper, ContentBasic },
}
</script>

<style scoped></style>
