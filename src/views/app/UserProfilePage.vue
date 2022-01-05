<template>
  <page-layout :title="$t('navigation.profile')">
    <v-row>
      <v-col v-if="false" cols="12" md="6">
        <setting-handler />
      </v-col>
      <v-col cols="12" md="6">
        <change-password-form />
      </v-col>
      <v-col cols="12" md="6">
        <license />
      </v-col>
    </v-row>
  </page-layout>
</template>

<script>
import PageLayout from '../../components/layout/PageLayout'
import SettingHandler from '../../components/app/profile/SettingHandler'
import ChangePasswordForm from '@/components/app/profile/form/ChangePasswordForm'
import License from '@/components/app/profile/License'

export default {
  name: 'UserProfilePage',
  components: {
    License,
    ChangePasswordForm,
    SettingHandler,
    PageLayout,
  },
}
</script>
