<template>
  <content-basic :title="$t('navigation.users')">
    <users-grid />
  </content-basic>
</template>

<script>
import UsersGrid from '../../components/app/users/grid/UsersGrid'
import ContentBasic from '@/components/layout/content/ContentBasic'

export default {
  name: 'UsersPage',
  components: { ContentBasic, UsersGrid },
}
</script>
