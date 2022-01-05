<template>
  <app-center-layout>
    <forgot-password-form-handler />
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'
import ForgotPasswordFormHandler from '../components/app/auth/forgotPassoword/ForgotPasswordFormHandler'

export default {
  name: 'ForgotPasswordPage',
  components: { AppCenterLayout, ForgotPasswordFormHandler },
}
</script>
