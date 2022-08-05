<template>
  <v-btn
    :to="to"
    fab
    x-small
    color="primary"
    elevation="0"
    :class="text ? 'button-text' : '' + 'sidebar-button'"
    @click="handleClick"
  >
    <span v-if="text">{{ text }}</span>
    <v-icon v-else class="navbar-size">{{ icon }}</v-icon>
  </v-btn>
</template>

<script>
export default {
  name: 'AppSpecialButton',
  props: {
    text: {
      type: String,
      default: '',
    },
    to: {
      type: [String, Object],
      default: '',
    },
    icon: {
      type: String,
      default: 'mdi-menu-right',
    },
  },
  methods: {
    handleClick() {
      this.$emit('click')
    },
  },
}
</script>

<style lang="scss" scoped>
.button-text {
  width: auto;
}
.navbar-size {
  font-size: 21px !important;
}
</style>
