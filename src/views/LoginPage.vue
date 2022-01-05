<template>
  <app-center-layout>
    <login-form-handler />
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'
import LoginFormHandler from '../components/app/auth/login/LoginFormHandler'

export default {
  name: 'LoginPage',
  components: { AppCenterLayout, LoginFormHandler },
}
</script>
