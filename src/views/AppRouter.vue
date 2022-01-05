<template>
  <app-layout />
</template>

<script>
import AppLayout from '../components/layout/AppLayout'

export default {
  name: 'AppRouter',
  components: { AppLayout },
  watch: {
    $route: {
      immediate: true,
      handler(to) {
        document.title = to.meta.title
      },
    },
  },
}
</script>
