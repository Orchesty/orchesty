<template>
  <v-menu
    :value="value"
    :nudge-right="40"
    transition="scale-transition"
    offset-y
    min-width="auto"
  >
    <template #activator="{ on, attrs }">
      <v-text-field
        :value="value"
        :label="label"
        :prepend-icon="prependIcon ? 'mdi-calendar' : ''"
        readonly
        v-bind="attrs"
        :disabled="disabled"
        :clearable="clearable"
        :outlined="outlined"
        :dense="dense"
        v-on="on"
        @click:clear="$emit('input', '')"
      />
    </template>
    <v-date-picker
      :value="value"
      :disabled="disabled"
      :readonly="readonly"
      :min="minDate"
      @input="handleInput"
    />
    <span v-if="renderErrorMessage" class="error--text">{{
      errorMessages[0]
    }}</span>
  </v-menu>
</template>

<script>
export default {
  name: "AppDatePickerInput",
  props: {
    value: {
      type: String,
      required: true,
    },
    disabled: {
      type: Boolean,
      default: false,
    },
    clearable: {
      type: Boolean,
      default: false,
    },
    label: {
      type: String,
      required: true,
    },
    readonly: {
      type: Boolean,
      default: false,
    },
    errorMessages: {
      type: Array,
      default: () => [],
    },
    prependIcon: {
      type: Boolean,
      default: true,
    },
    outlined: {
      type: Boolean,
      default: true,
    },
    dense: {
      type: Boolean,
      default: true,
    },
    minDate: {
      type: String,
      required: false,
      default: "",
    },
  },
  computed: {
    renderErrorMessage() {
      return this.errorMessages?.length > 0
    },
  },
  data() {
    return {
      open: false,
    }
  },
  methods: {
    handleInput(val) {
      this.$emit("input", val)
      this.open = false
    },
  },
}
</script>
