<template>
  <v-combobox
    :label="label"
    :placeholder="placeholder"
    :prepend-icon="prependIcon"
    v-model="model"
    :items="items"
    :loading="isLoading"
    :search-input.sync="search"
    :item-text="itemText"
    :item-value="itemValue"
    chips
    deletable-chips
    :multiple="multiple"
    hide-selected
    :error-messages="errorMessages"
    outlined
    dense
    @focus.once="initSearch"
  />
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator"

@Component({})
export default class Combobox extends Vue {
  @Prop({ type: Function, required: true })
  private onSearch!: { (val: string): Array<any> }
  @Prop({ type: Boolean })
  private isLoading = false
  @Prop({ type: String, default: "" })
  private label!: string
  @Prop({ type: String, default: "" })
  private placeholder!: string
  @Prop({ type: String, required: true })
  private itemText!: string
  @Prop({ type: String, required: true })
  private itemValue!: string
  @Prop({ type: Boolean, default: false })
  private multiple!: boolean
  @Prop({ type: Array, default: () => [], required: false })
  private errorMessages!: boolean
  @Prop({ type: [Array, String, Object], required: false, default: () => null })
  private value!: any
  @Prop({ type: String, default: "" })
  private prependIcon!: string

  private search = ""
  private model: any = null
  private items: Array<any> = []
  private timeout: number | null = null

  constructor() {
    super()
    this.model = this.value
  }

  @Watch("search")
  private async searchItems(val: string) {
    if (!val) return

    if (this.timeout) {
      clearTimeout(this.timeout)
    }
    this.timeout = setTimeout(async () => {
      this.items = await this.onSearch(val)
    }, 300)
  }

  private async initSearch() {
    this.items = await this.onSearch("")
  }

  @Watch("model")
  onSelect(val: any) {
    if (!val) return

    this.search = ""
    this.$emit("input", val)
  }

  @Watch("value")
  onValue(val: any) {
    this.model = val
  }
}
</script>
