<template>
  <heading class="text-center">
    {{ $t('notFoundPage.heading') }}
  </heading>
</template>

<script>
import Heading from '@/components/commons/Heading'
export default {
  name: 'NotFoundPage',
  components: { Heading },
}
</script>
