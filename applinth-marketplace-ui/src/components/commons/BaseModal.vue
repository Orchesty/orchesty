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
          <heading>{{ title }}</heading>
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
import Heading from '@/components/commons/Heading'
export default {
  name: 'BaseModal',
  components: { Heading },
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
