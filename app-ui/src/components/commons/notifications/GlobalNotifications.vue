<template>
  <v-snackbar v-model="snackbar" top :color="type" :timeout="timeout">
    <div class="d-flex align-center justify-around">
      <span class="font-weight-bold">{{ text }}</span>
      <v-btn dark text small class="ml-auto" @click="snackbar = false">
        {{ $t('button.close') }}
      </v-btn>
    </div>
  </v-snackbar>
</template>

<script>
import { mapState, mapActions } from 'vuex'
import { FLASH_MESSAGES, FLASH_MESSAGES_TYPES } from '../../../store/modules/flashMessages/types'

export default {
  name: 'GlobalNotifications',
  computed: {
    ...mapState(FLASH_MESSAGES.NAMESPACE, ['flashMessages']),
  },
  data() {
    return {
      id: null,
      text: null,
      type: null,
      snackbar: false,
      timeout: 5000,
    }
  },
  methods: {
    ...mapActions(FLASH_MESSAGES.NAMESPACE, [FLASH_MESSAGES.ACTIONS.REMOVE]),
    getType(type) {
      switch (type) {
        case FLASH_MESSAGES_TYPES.INFO: {
          return 'info'
        }
        case FLASH_MESSAGES_TYPES.SUCCESS: {
          return 'success'
        }
        case FLASH_MESSAGES_TYPES.ERROR: {
          return 'error'
        }
        default: {
          return undefined
        }
      }
    },
    setSnackbar(notification) {
      this.id = notification.id
      this.text = notification.message
      this.type = this.getType(notification.type)
    },
    clearSnackbar() {
      this.id = null
      this.text = null
      this.type = null
    },
  },
  watch: {
    flashMessages() {
      const last = this.flashMessages[this.flashMessages.length - 1]

      if (last) {
        this.setSnackbar(last)
        this.snackbar = true
      }
    },
    snackbar(value) {
      if (value === false) {
        this[FLASH_MESSAGES.ACTIONS.REMOVE]({ id: this.id })
        this.clearSnackbar()
      }
    },
  },
}
</script>
