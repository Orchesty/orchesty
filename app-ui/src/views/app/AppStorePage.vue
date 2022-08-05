<template>
  <content-tabs :title="$t('navigation.appStore')" :tabs="tabs" />
</template>

<script>
import ContentTabs from '@/components/layout/content/ContentTabs'
import { ROUTES } from '@/services/enums/routerEnums'

export default {
  name: 'AppStorePage',
  components: {
    ContentTabs,
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
