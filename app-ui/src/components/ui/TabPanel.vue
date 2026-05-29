<script setup lang="ts">
interface Props {
  id: string
  ariaLabelledby: string
  hidden?: boolean
}

withDefaults(defineProps<Props>(), {
  hidden: false,
})
</script>

<template>
  <div :id="id" role="tabpanel" :aria-labelledby="ariaLabelledby" :class="{ hidden }">
    <slot />
  </div>
</template>

