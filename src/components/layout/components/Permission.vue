<template>
  <div v-if="isAllowed">
    <slot> </slot>
  </div>
  <div v-else class="text-center">
    <h1>{{ $t('acl.notAllowed') }}</h1>
  </div>
</template>

<script>
export default {
  name: 'Permission',
  props: {
    isAllowed: {
      type: Boolean,
      required: true,
    },
  },
}
</script>
