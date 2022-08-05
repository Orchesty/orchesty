<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-switch
      class="mt-n1 ml-3"
      :label="label"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :disabled="disabled"
      dense
      :hint="hint"
      :persistent-hint="!!hint"
      :hide-details="hideDetails"
      :prepend-inner-icon="prependInnerIcon"
      :false-value="0"
      :true-value="1"
      @change="(val) => $emit('input', val)"
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
export default class SwitchField extends Vue {
  @Prop({ required: false, type: String })
  private label?: string;

  @Prop({ required: true, type: String })
  private name!: string;

  @Prop({ required: false, type: String, default: "" })
  private hint!: string;

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

<style lang="scss" scoped></style>
