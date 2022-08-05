<template>
  <auth-layout>
    <div class="text-center">
      <h1>{{ $t('notFound.title') }}</h1>
    </div>
  </auth-layout>
</template>

<script>
import AuthLayout from '@/components/layout/auth/AuthLayout'

export default {
  name: 'NotFoundPage',
  components: { AuthLayout },
}
</script>
