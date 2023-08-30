<template>
  <validation-provider v-slot="{ errors, valid }" :rules="rules" :name="label.toLowerCase()">
    <v-select
      v-model="innerValue"
      :items="items"
      :label="label"
      :error-messages="errors[0]"
      outlined
      dense
      :hide-details="valid"
    />
  </validation-provider>
</template>

<script>
import { ValidationProvider } from 'vee-validate'

export default {
  name: 'BooleanInput',
  components: { ValidationProvider },
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
      type: String,
      required: false,
      default: '',
    },
    onChange: {
      type: Function,
      required: true,
    },
    rules: {
      type: [Object, String],
      default: '',
    },
    allowAllItem: {
      type: Boolean,
      default: false,
    },
  },
  data() {
    return {
      innerValue: this.value,
    }
  },
  computed: {
    items() {
      const items = []

      if (this.allowAllItem) {
        items.push({
          text: this.$i18n.t('dataGrid.inputs.boolean.all'),
          value: '',
        })
      }

      items.push({
        text: this.$i18n.t('dataGrid.inputs.boolean.true'),
        value: 'true',
      })

      items.push({
        text: this.$i18n.t('dataGrid.inputs.boolean.false'),
        value: 'false',
      })

      return items
    },
  },
  watch: {
    value(newVal) {
      this.innerValue = newVal
    },
    innerValue() {
      this.onChange(this.innerValue)
    },
  },
}
</script>
