<template>
  <base-modal
    v-model="isOpen"
    :title="$t('application.applicationNotAuthorized')"
  >
    <template #content>
      <div class="d-flex flex-column">
        <p class="text-body-1">
          {{ $t("application.applicationNotAuthorizedMessage") }}
        </p>
      </div>
    </template>
    <template #actions>
      <base-button :on-click="toggleModal" :button-title="$t('button.ok')" />
    </template>
  </base-modal>
</template>

<script>
import BaseModal from "@/components/commons/BaseModal"
import BaseButton from "@/components/commons/BaseButton"

export default {
  name: "AppNotAuthorizedModal",
  components: { BaseButton, BaseModal },
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
      this.$emit("input", val)
    },
  },
}
</script>

<style scoped></style>
