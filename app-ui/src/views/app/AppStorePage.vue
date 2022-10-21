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
          name: 'page.heading.availableApps',
          route: ROUTES.APP_STORE.AVAILABLE_APPS,
        },
        {
          name: 'page.heading.installedApps',
          route: ROUTES.APP_STORE.INSTALLED_APPS,
        },
      ],
    }
  },
}
</script>
