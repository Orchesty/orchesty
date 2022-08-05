<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-combobox
      :value="value"
      :label="label"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :disabled="disabled"
      outlined
      dense
      @input="(val) => $emit('input', val)"
      :hide-details="hideDetails"
      :prepend-inner-icon="prependInnerIcon"
      multiple
      small-chips
    />
  </ValidationProvider>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { ValidationProvider } from "vee-validate";
import { Rules } from "../../../utils/veeValidate";

@Component({
  components: {
    ValidationProvider,
  },
})
export default class TextFieldMultiple extends Vue {
  @Prop({ required: false, type: Array, default: [] })
  private value!: number | string;

  @Prop({ required: false, type: String })
  private label?: string;

  @Prop({ required: true, type: String })
  private name!: string;

  @Prop({ required: false, type: Object })
  private rules?: Rules;

  @Prop({ required: false, type: Boolean, default: false })
  private autofocus!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private hideDetails!: boolean;

  @Prop({ required: false, type: String })
  private prependInnerIcon?: string;
}
</script>

<style lang="scss" scoped>
::v-deep .v-input__append-inner {
  display: none;
}
</style>
