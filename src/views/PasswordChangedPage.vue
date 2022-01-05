<template>
  <app-center-layout>
    <password-changed />
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'
import PasswordChanged from '../components/app/auth/restorePassword/PasswordChangedSuccessfully'

export default {
  name: 'PasswordChangedPage',
  components: { AppCenterLayout, PasswordChanged },
}
</script>
