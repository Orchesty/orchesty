<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-menu
      v-model="isOpen"
      :close-on-content-click="false"
      offset-y
      transition="scale-transition"
      min-width="290px"
      :disabled="readonly"
    >
      <template v-slot:activator="{ on }">
        <v-text-field
          :clearable="!readonly"
          class=""
          :label="label"
          :hint="hint"
          :value="value | toLocalDate"
          :error-messages="errors[0]"
          :persistent-hint="!!hint"
          v-on="on"
          readonly
          hide-details
          dense
          outlined
        />
      </template>
      <v-date-picker
        v-model="innerValue"
        :disabled="disabled"
        :locale="$i18n.locale"
        :first-day-of-week="1"
        @input="isOpen = false"
        @change="(val) => $emit('input', formatForFilter(val))"
      />
    </v-menu>
  </ValidationProvider>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import { ValidationProvider } from "vee-validate"
import { Rules } from "../../../utils/veeValidate"
import { toLocalDate } from "../../../filters/datetime"

@Component({
  components: {
    ValidationProvider,
  },
  filters: {
    toLocalDate,
  },
})
export default class DateField extends Vue {
  private isOpen: boolean
  private innerValue: any

  @Prop({ required: false, type: [String], default: "" })
  private value!: string

  @Prop({ required: false, type: String })
  private label?: string

  @Prop({ required: true, type: String })
  private name!: string

  @Prop({ required: false, type: String, default: "" })
  private hint!: string

  @Prop({ required: false, type: [Object, String] })
  private rules?: Rules

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private allDay!: boolean

  @Prop({ type: Boolean, default: false })
  readonly!: boolean

  constructor() {
    super()
    this.isOpen = false
    this.innerValue = null
  }

  formatForFilter(dateTime: string) {
    const day = new Date(dateTime)
    if (this.allDay) {
      day.setUTCHours(23, 59, 59)
    }

    return day.toISOString()
  }
}
</script>
<style lang="scss" scoped></style>
