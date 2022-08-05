<template>
  <v-snackbar v-model="snackbarOpen">
    {{ $t(flashMessage) }}

    <template #action="{ attrs }">
      <v-btn color="pink" text v-bind="attrs" @click="snackbarOpen = false">
        {{ $t('button.close') }}
      </v-btn>
    </template>
  </v-snackbar>
</template>

<script>
import { mapActions, mapState } from 'vuex'

export default {
  name: 'FlashMessages',
  data() {
    return {
      snackbarOpen: false,
    }
  },
  computed: {
    ...mapState('flashMessages', ['flashMessage']),
  },
  methods: {
    ...mapActions('flashMessages', ['flashMessageSet', 'flashMessageRemove']),
  },
  watch: {
    snackbarOpen(value) {
      if (value === false) {
        this.flashMessageRemove()
      }
    },
    flashMessage(value) {
      if (value) {
        this.snackbarOpen = true
      }
    },
  },
}
</script>

<style scoped></style>
