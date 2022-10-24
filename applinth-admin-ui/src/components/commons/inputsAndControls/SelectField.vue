<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-select
      class="ml-2"
      v-model="innerValue"
      :hint="hint"
      :error-messages="errors[0]"
      :items="values"
      item-text="name"
      item-value="value"
      :label="label"
      :persistent-hint="!!hint"
      return-object
      single-line
      @change="(val) => $emit('input', reduceForFilter(val))"
    />
  </ValidationProvider>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import { ValidationProvider } from "vee-validate"
import { Rules } from "../../../utils/veeValidate"
import { EnumValues } from "../tables/types"

@Component({
  components: {
    ValidationProvider,
  },
})
export default class SelectField extends Vue {
  private innerValue: any

  @Prop({ required: false, type: Array, default: "" })
  private values!: EnumValues[]

  @Prop({ required: false, type: String })
  private label?: string

  @Prop({ required: true, type: String })
  private name!: string

  @Prop({ required: false, type: String, default: "" })
  private hint!: string

  @Prop({ required: false, type: Object })
  private rules?: Rules

  constructor() {
    super()
    this.innerValue = null
  }

  reduceForFilter(selected: EnumValues): any {
    return selected.value
  }
}
</script>
<style lang="scss" scoped></style>
