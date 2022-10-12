<template>
  <modal-template v-model="isOpen" :title="$t('appStore.applicationNotAuthorized')">
    <template #default>
      <div class="d-flex flex-column">
        <p class="text-body-1">
          {{ $t('appStore.applicationNotAuthorizedMessage') }}
        </p>
      </div>
    </template>
    <template #sendingButton>
      <app-button :on-click="toggleModal" :button-title="$t('button.ok')" />
    </template>
  </modal-template>
</template>

<script>
import AppButton from '../../../commons/button/AppButton'
import ModalTemplate from '../../../commons/modal/ModalTemplate'

export default {
  name: 'AppNotAuthorizedModal',
  components: { ModalTemplate, AppButton },
  props: {
    value: {
      type: Boolean,
      required: true,
    },
  },
  data() {
    return {
      isOpen: false,
    }
  },
  methods: {
    toggleModal() {
      this.isOpen = !this.isOpen
    },
  },
  watch: {
    value: {
      immediate: true,
      handler(val) {
        this.isOpen = val
      },
    },
    isOpen(val) {
      this.$emit('input', val)
    },
  },
}
</script>

<style scoped></style>
