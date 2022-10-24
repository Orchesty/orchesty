<template>
  <ValidationProvider v-slot="{ errors }" :name="name" :rules="rules || {}">
    <v-autocomplete
      dense
      :items="items"
      :item-text="itemText"
      :item-value="itemValue"
      :label="label"
      :value="value"
      :search-input.sync="search"
      :error-messages="errors[0]"
      :autofocus="autofocus"
      :disabled="disabled"
      :hide-details="hideDetails"
      :solo-inverted="soloInverted"
      :flat="flat"
      :style="width ? `width: ${width};` : ''"
      :menu-props="{ offsetY: true }"
      :outlined="!flat"
      :loading="loading"
      :clearable="clearable"
      :multiple="multiple"
      :small-chips="multiple"
      :deletable-chips="multiple"
      :placeholder="placeholder"
      :hide-selected="hideSelected"
      :hint="hint"
      :persistent-hint="!!hint"
      :prepend-icon="prependIcon"
      :filter="overriddenFilter"
      @change="onChange"
      @input="(val) => $emit('input', val)"
      @focus.once="$emit('search', '')"
      @click:clear="$emit('search', null)"
    />
  </ValidationProvider>
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator"
import { ValidationProvider } from "vee-validate"
import { Rules } from "../../../utils/veeValidate"
import { remove } from "diacritics"

@Component({
  components: {
    ValidationProvider,
  },
})
export default class Autocomplete extends Vue {
  @Prop({ required: true, type: String })
  private name!: string

  @Prop({ required: false, type: String, default: "" })
  private label!: string

  @Prop({ required: false, type: [String, Number, Array], default: "" })
  private value!: string | number | any[]

  @Prop({ required: false, type: Object })
  private rules?: Rules

  @Prop({ required: false, type: Boolean, default: false })
  private autofocus!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private soloInverted!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private flat!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private disabled!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private hideDetails!: boolean

  @Prop({ required: true, type: Array })
  private items!: { value: string | number; text: string }[]

  @Prop({ required: false, type: String, default: "text" })
  private itemText!: string

  @Prop({ required: false, type: String, default: "value" })
  private itemValue!: string

  @Prop({ required: false, type: String })
  private width?: string

  @Prop({ required: false, type: Boolean, default: false })
  private loading!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private clearable!: boolean

  @Prop({ required: false, type: Boolean, default: false })
  private multiple!: boolean

  @Prop({ required: false, type: String, default: "" })
  private placeholder!: string

  @Prop({ required: false, type: Boolean, default: false })
  private hideSelected!: boolean

  @Prop({ required: false, type: String, default: "" })
  private hint!: string

  @Prop({ required: false, type: String, default: "" })
  private prependIcon!: string

  private timeout: number | null = null

  private search = ""

  private overriddenFilter(item: any, queryText: string, itemText: string) {
    const text = remove(itemText).toLocaleLowerCase()
    const query = remove(queryText).toLocaleLowerCase()

    return text.indexOf(query) > -1
  }

  private onChange(newVal: string[]): void {
    this.search = ""
    this.$emit("change", newVal)
  }

  @Watch("search")
  private searchItems(val: string): void {
    if (!val) return
    if (this.timeout) {
      clearTimeout(this.timeout)
    }
    this.timeout = setTimeout(() => {
      this.$emit("search", val)
    }, 300)
  }
}
</script>

<style lang="scss" scoped></style>
