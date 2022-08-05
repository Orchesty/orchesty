<template>
  <v-btn
    class="app-item-redirect"
    :to="to"
    :disabled="loading ? true : disabled"
    :color="white ? 'white' : red ? `error` : `primary`"
    small
    min-width="104"
    :text="installed"
    :elevation="white ? 2 : 0"
    @click="handleClick"
  >
    <span class="subtitle-2">{{ loading ? loadingText : text }}</span>
  </v-btn>
</template>

<script>
export default {
  name: 'AppItemButton',
  props: {
    disabled: {
      type: Boolean,
      default: false,
    },
    text: {
      type: String,
      default: '',
    },
    loadingText: {
      type: String,
      default: '',
    },
    to: {
      type: [String, Object],
      default: '',
    },
    loading: {
      type: Boolean,
      default: false,
    },
    icon: {
      type: String,
      default: 'mdi-menu-right',
    },
    white: {
      type: Boolean,
      default: false,
    },
    red: {
      type: Boolean,
      default: false,
    },
    installed: {
      type: Boolean,
      default: false,
    },
  },
  methods: {
    handleClick() {
      this.$emit('click')
    },
  },
}
</script>
