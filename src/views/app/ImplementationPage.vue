<template>
  <content-basic :title="$t('navigation.implementations')">
    <implementation-grid />
  </content-basic>
</template>

<script>
import ContentBasic from '@/components/layout/content/ContentBasic'
import ImplementationGrid from '@/components/app/implementations/grid/ImplementationGrid'

export default {
  name: 'ImplementationPage',
  components: { ContentBasic, ImplementationGrid },
}
</script>
