<template>
  <app-center-layout>
    <div class="text-center">
      <h1>{{ $t('notFound.title') }}</h1>
    </div>
  </app-center-layout>
</template>

<script>
import AppCenterLayout from '../components/layout/AppCenterLayout'

export default {
  name: 'NotFoundPage',
  components: { AppCenterLayout },
}
</script>
