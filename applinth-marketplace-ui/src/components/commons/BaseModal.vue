<template>
  <v-dialog
    v-model="isOpen"
    transition="dialog-bottom-transition"
    max-width="800"
  >
    <template #activator="{ on, attrs }">
      <slot name="activator" :bind="attrs" :on="on"></slot>
    </template>
    <template #default>
      <v-card>
        <v-toolbar color="primary" dark>
          <sub-heading>{{ title }}</sub-heading>
        </v-toolbar>
        <v-card-text class="py-6 black--text">
          <slot name="content"></slot>
        </v-card-text>
        <v-card-actions class="justify-end">
          <slot name="actions"></slot>
        </v-card-actions>
      </v-card>
    </template>
  </v-dialog>
</template>

<script>
import SubHeading from '@/components/commons/SubHeading'
export default {
  name: 'BaseModal',
  components: { SubHeading },
  props: {
    title: {
      type: String,
      required: true,
    },
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
  watch: {
    value(value) {
      this.isOpen = value
    },
    isOpen(isOpen) {
      this.$emit('input', isOpen)
    },
  },
}
</script>

<style scoped></style>
