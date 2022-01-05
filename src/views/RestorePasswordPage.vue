<template>
  <app-center-layout>
    <restore-password-form-handler />
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'
import RestorePasswordFormHandler from '../components/app/auth/restorePassword/RestorePasswordFormHandler'

export default {
  name: 'RestorePasswordPage',
  components: { AppCenterLayout, RestorePasswordFormHandler },
}
</script>
