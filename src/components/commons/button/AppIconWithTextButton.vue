<template>
  <v-btn
    elevation="0"
    class="px-2"
    :disabled="disabled"
    :loading="loading"
    @click="handleClick"
  >
    <slot name="icon" />
    <span class="mx-1">{{ text }}</span>
  </v-btn>
</template>

<script>
export default {
  name: "AppIconWithTextButton",
  props: {
    text: {
      type: String,
      default: "",
      required: true,
    },
    loading: {
      default: false,
      type: Boolean,
    },
    disabled: {
      default: false,
      type: Boolean,
    },
  },
  methods: {
    handleClick() {
      this.$emit("click")
    },
  },
}
</script>

<style scoped></style>
