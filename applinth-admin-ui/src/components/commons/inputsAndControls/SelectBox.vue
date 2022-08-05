<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-select
      :items="items"
      :item-text="itemText"
      :item-value="itemValue"
      :label="label"
      :value="value"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :disabled="disabled"
      :hide-details="hideDetails"
      dense
      :solo-inverted="soloInverted"
      :flat="flat"
      @input="(val) => $emit('input', val)"
      :style="width ? `width: ${width};` : ''"
      @change="(val) => $emit('change', val)"
      :menu-props="{ offsetY: true }"
      :outlined="!flat"
      :clearable="clearable"
      :placeholder="placeholder"
      no-data-text="Žádná data"
    >
      <template v-if="customData" #item="{ item }">
        <slot :item="item" />
      </template>
      <template v-if="customData" #selection="{ item }">
        <slot name="selection" :item="item" />
      </template>
    </v-select>
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
  @Prop({ required: true, type: String })
  private name!: string;

  @Prop({ required: false, type: String, default: "" })
  private label!: string;

  @Prop({ required: false, type: [String, Number], default: "" })
  private value!: string | number;

  @Prop({ required: false, type: Boolean, default: false })
  private customData!: boolean;

  @Prop({ required: false, type: [Object, String] })
  private rules?: Rules;

  @Prop({ required: false, type: String, default: "text" })
  private type!: "number" | "password" | "text";

  @Prop({ required: false, type: Boolean, default: false })
  private autofocus!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private soloInverted!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private flat!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private hideDetails!: boolean;

  @Prop({ required: true, type: Array })
  private items!: Array<Record<string, string>>;

  @Prop({ required: false, type: String, default: "text" })
  private itemText!: string;

  @Prop({ required: false, type: String, default: "value" })
  private itemValue!: string;

  @Prop({ required: false, type: String })
  private width?: string;

  @Prop({ required: false, type: Boolean, default: false })
  private clearable!: boolean;

  @Prop({ required: false, type: String, default: () => null })
  private placeholder!: string | null;
}
</script>

<style lang="scss" scoped></style>
