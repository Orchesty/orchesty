<template>
  <app-center-layout>
    <registration-confirm-form-handler />
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'
import RegistrationConfirmFormHandler from '../components/app/auth/register/RegistrationConfirmFormHandler'

export default {
  name: 'RestorePasswordPage',
  components: { AppCenterLayout, RegistrationConfirmFormHandler },
}
</script>
