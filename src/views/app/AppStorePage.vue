<template>
  <page-tabs-layout :title="$t('navigation.appStore')" :tabs="tabs" />
</template>

<script>
import PageTabsLayout from '@/components/layout/PageTabsLayout'
import { ROUTES } from '@/router/routes'

export default {
  name: 'AppStorePage',
  components: {
    PageTabsLayout,
  },
  data() {
    return {
      tabs: [
        {
          name: 'appStore.available',
          route: ROUTES.APP_STORE.AVAILABLE_APPS,
        },
        {
          name: 'appStore.installed',
          route: ROUTES.APP_STORE.INSTALLED_APPS,
        },
      ],
    }
  },
}
</script>
