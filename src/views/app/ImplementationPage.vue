<template>
  <page-layout :title="$t('navigation.implementations')">
    <implementation-grid-handler />
  </page-layout>
</template>

<script>
import PageLayout from '@/components/layout/PageLayout'
import ImplementationGridHandler from '@/components/app/implementations/grid/ImplementationGridHandler'

export default {
  name: 'ImplementationPage',
  components: { ImplementationGridHandler, PageLayout },
}
</script>
