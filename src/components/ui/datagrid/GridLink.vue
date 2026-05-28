<script setup lang="ts">
import type { RouteLocationRaw } from 'vue-router'

defineProps<{
  to: RouteLocationRaw
}>()
</script>

<template>
  <router-link :to="to" class="whitespace-nowrap font-medium text-gray-900 hover:underline dark:text-white">
    <slot />
  </router-link>
</template>
