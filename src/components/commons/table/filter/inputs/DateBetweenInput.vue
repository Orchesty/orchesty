<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-menu v-model="isOpen" :close-on-content-click="false" offset-y transition="scale-transition" min-width="290px">
      <template #activator="{ on }">
        <v-text-field
          :label="label"
          readonly
          :value="innerValue.join(' ~ ')"
          :error-messages="errors[0]"
          :hide-details="valid"
          dense
          outlined
          clearable
          @click:clear="onClear"
          v-on="on"
        />
      </template>
      <v-date-picker v-model="innerValue" :locale="$i18n.locale" range :first-day-of-week="1" />
    </v-menu>
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'

export default {
  name: 'DateTimeBetweenInput',
  components: {
    ValidationProvider,
  },
  props: {
    label: {
      type: String,
      required: true,
    },
    column: {
      type: String,
      required: false,
      default: '',
    },
    value: {
      type: Array,
      required: false,
      default: () => [],
    },
    onChange: {
      type: Function,
      required: true,
    },
    rules: {
      type: [Object, String],
      default: '',
    },
  },
  data() {
    return {
      innerValue: this.value || null,
      isOpen: false,
    }
  },
  computed: {
    from() {
      return `${this.label} - ${this.$i18n.t('dataGrid.inputs.between.from')}`
    },
    to() {
      return `${this.label} - ${this.$i18n.t('dataGrid.inputs.between.to')}`
    },
  },
  methods: {
    onClear() {
      this.innerValue = []
    },
  },
  watch: {
    value(newVal) {
      this.innerValue = newVal || null
    },
    innerValue() {
      this.onChange(this.innerValue)
    },
  },
}
</script>
