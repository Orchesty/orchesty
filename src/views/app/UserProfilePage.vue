<template>
  <content-basic :title="$t('navigation.profile')">
    <v-row>
      <v-col cols="12" md="6">
        <change-password-form />
      </v-col>
      <v-col cols="12" md="6">
        <license />
      </v-col>
    </v-row>
  </content-basic>
</template>

<script>
import ChangePasswordForm from '@/components/app/profile/form/ChangePasswordForm'
import License from '@/components/app/profile/License'
import ContentBasic from '@/components/layout/content/ContentBasic'

export default {
  name: 'UserProfilePage',
  components: {
    ContentBasic,
    License,
    ChangePasswordForm,
  },
}
</script>
