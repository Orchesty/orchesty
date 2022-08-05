<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-textarea
      :value="value"
      :label="label"
      :type="type"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :disabled="disabled"
      outlined
      :counter="counter"
      dense
      :hint="hint"
      :persistent-hint="!!hint"
      @input="(val) => $emit('input', val)"
      :hide-details="hideDetails"
      :prepend-inner-icon="prependInnerIcon"
      :rows="rows"
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
export default class Textarea extends Vue {
  @Prop({ required: false, type: [Number, String], default: "" })
  private value!: number | string;

  @Prop({ required: false, type: String })
  private label?: string;

  @Prop({ required: true, type: String })
  private name!: string;

  @Prop({ required: false, type: [Object, String] })
  private rules?: Rules;

  @Prop({ required: false, type: String, default: "" })
  private hint!: string;

  @Prop({ required: false, type: String, default: "text" })
  private type!: "number" | "password" | "text";

  @Prop({ required: false, type: Boolean, default: false })
  private autofocus!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private hideDetails!: boolean;

  @Prop({ required: false, type: String })
  private prependInnerIcon?: string;

  @Prop({ required: false, type: Number, default: 4 })
  private rows!: number;

  @Prop({ required: false, type: [Boolean, Number], default: false })
  private counter!: boolean | number;
}
</script>

<style lang="scss" scoped>
::v-deep .v-textarea textarea {
  line-height: 1.5em;
}
</style>
