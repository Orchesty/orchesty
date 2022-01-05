<template>
  <page-layout :title="$t('navigation.users')">
    <users-grid-handler />
  </page-layout>
</template>

<script>
import PageLayout from '../../components/layout/PageLayout'
import UsersGridHandler from '../../components/app/users/grid/UsersGridHandler'

export default {
  name: 'UsersPage',
  components: { PageLayout, UsersGridHandler },
}
</script>
