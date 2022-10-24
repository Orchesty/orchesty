<template>
  <ValidationProvider :name="name" :rules="rules" v-slot="{ errors }">
    <v-menu
      v-model="isOpenMenu"
      absolute
      style="display: none"
      top
      nudge-bottom="105"
      nudge-left="16"
      :close-on-content-click="false"
    >
      <template v-slot:activator="{ on }">
        <v-text-field
          v-model="innerValue"
          :label="label"
          v-on="on"
          :error-messages="errors"
          dense
          outlined
          :placeholder="placeholder"
        >
          <template v-slot:append>
            <div
              v-on="on"
              class="color"
              :style="{ backgroundColor: innerValue }"
            />
          </template>
        </v-text-field>
      </template>
      <v-card>
        <v-card-text class="pa-0">
          <v-color-picker mode="hexa" v-model="innerValue" flat />
        </v-card-text>
      </v-card>
    </v-menu>
  </ValidationProvider>
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator"
import { ValidationProvider } from "vee-validate"
import { Rules } from "../../../utils/veeValidate"

@Component({
  components: {
    ValidationProvider,
  },
})
export default class ColorPickerInput extends Vue {
  @Prop({ type: String })
  private value!: string

  @Prop({ required: false, type: String })
  private label?: string

  @Prop({ required: false, type: String, default: "" })
  private name!: string

  @Prop({ required: false, type: String, default: "" })
  private placeholder!: string

  @Prop({ required: false, type: Object })
  private rules?: Rules

  private innerValue = ""
  private isOpenMenu = false

  constructor() {
    super()
    this.innerValue = this.value
  }

  @Watch("value")
  onChangeValue() {
    this.innerValue = this.value
  }

  @Watch("innerValue")
  onChangeInnerValue() {
    this.$emit("input", this.innerValue)
  }
}
</script>

<style lang="scss" scoped>
.color {
  width: 25px;
  height: 25px;
  border: 1px solid lightgray;
  cursor: pointer;
}
</style>
