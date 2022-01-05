<template>
  <page-layout :title="$t('navigation.trash')">
    <trash-handler />
  </page-layout>
</template>

<script>
import PageLayout from '@/components/layout/PageLayout'
import TrashHandler from '@/components/app/trash/grid/TrashHandler'
export default {
  name: 'TrashPage',
  components: { TrashHandler, PageLayout },
}
</script>
