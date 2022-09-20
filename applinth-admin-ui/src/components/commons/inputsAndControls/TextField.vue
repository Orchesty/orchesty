<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-text-field
      :value="value"
      :type="type"
      :readonly="readonly"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :autocomplete="autocomplete"
      :disabled="disabled"
      outlined
      :placeholder="placeholder"
      :counter="counter"
      :maxLength="maxlength"
      dense
      :label="label"
      :hint="hint"
      :persistent-hint="!!hint"
      @input="(val) => $emit('input', val)"
      :hide-details="hideDetails"
      :prepend-inner-icon="prependInnerIcon"
      @blur="$emit('blur')"
      @keydown.enter="onEnterPress"
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
export default class TextField extends Vue {
  @Prop({ required: false, type: [Number, String], default: "" })
  private value!: number | string;

  @Prop({ required: false, type: String })
  private label?: string;

  @Prop({ required: true, type: String })
  private name!: string;

  @Prop({ required: false, type: String, default: "" })
  private hint!: string;

  @Prop({ required: false, type: [Object, String] })
  private rules?: Rules;

  @Prop({ required: false, type: String, default: () => null })
  private placeholder!: string | null;

  @Prop({ required: false, type: String, default: "text" })
  private type!: "number" | "password" | "text";

  @Prop({ required: false, type: Boolean, default: false })
  private autofocus!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private readonly!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private hideDetails!: boolean;

  @Prop({ required: false, type: String })
  private prependInnerIcon?: string;

  @Prop({ required: false, type: [Number, Boolean], default: false })
  private counter!: number | boolean;

  @Prop({ required: false, type: Number })
  private maxlength?: number;

  @Prop({ type: Boolean, default: false })
  private bigLabel?: boolean;

  @Prop({ type: String, default: () => null })
  private autocomplete?: string | null;

  @Prop({ required: false, type: Function, default: () => null })
  private blur!: any;

  @Prop({ required: false, type: Function, default: () => null })
  private onEnterPress!: any;
}
</script>

<style lang="scss" scoped>
.label {
  font-size: 12px;
  margin-bottom: 4px;
}

.big-label {
  font-size: 1rem;
  font-weight: 500;
  margin-bottom: 6px;
}
</style>
