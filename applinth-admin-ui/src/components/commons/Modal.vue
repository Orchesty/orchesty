<template>
  <v-dialog
    v-model="isOpen"
    content-class="modal"
    :persistent="isSending || persistent"
    :max-width="maxWidth"
    :fullscreen="fullscreen"
    :retain-focus="retainFocus"
  >
    <v-card class="card" :loading="isLoading">
      <v-card-title>
        <template v-if="title">
          <div class="text-h2">{{ title }}</div>
        </template>
        <v-spacer />
        <v-btn
          v-if="closeable"
          class="mt-n2 mr-n4"
          icon
          color="black"
          @click="handleCancel"
        >
          <v-icon>mdi-close</v-icon>
        </v-btn>
      </v-card-title>

      <v-divider v-if="body || $slots.default" />

      <v-card-text
        v-if="body || $slots.default"
        :class="{ 'card-text': true, 'pt-5': !noGutter, 'pa-0': noGutter }"
      >
        <template v-if="body">{{ body }}</template>
        <slot></slot>
      </v-card-text>

      <v-divider v-if="body || $slots.default" />

      <v-card-actions>
        <v-spacer />
        <slot name="actions-left" />
        <Button
          :disabled="isSending"
          v-if="cancelBtnText"
          @click="handleCancel"
          color="secondary"
          outlined
        >
          {{ cancelBtnText }}
        </Button>
        <Button
          :disabled="isLoading"
          :loading="isSending"
          v-if="confirmBtnText"
          @click="handleConfirm"
          color="secondary"
        >
          {{ confirmBtnText }}
        </Button>
        <slot name="actions-right" />
      </v-card-actions>
    </v-card>
  </v-dialog>
</template>

<script lang="ts">
import { Component, Prop, Vue, Watch } from "vue-property-decorator";
import Button from "./inputsAndControls/Button.vue";

@Component({
  components: {
    Button,
  },
})
export default class Modal extends Vue {
  @Prop({ required: false, type: Boolean, default: false })
  private value!: boolean;

  @Prop({ required: false, type: [String, Number], default: 550 })
  private maxWidth!: number | string;

  @Prop({ required: false, type: Boolean, default: false })
  private persistent!: boolean;

  @Prop({ required: false, type: Boolean, default: true })
  private closeable!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private isLoading!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private isSending!: boolean;

  @Prop({ required: false, type: String })
  private title?: string;

  @Prop({ required: false, type: String })
  private body?: string;

  @Prop({ required: false, type: String })
  private cancelBtnText?: string;

  @Prop({ required: false, type: String })
  private confirmBtnText?: string;

  @Prop({ required: false, type: Function })
  private onCancel?: () => void;

  @Prop({ required: false, type: Function })
  private onConfirm?: () => void;

  @Prop({ required: false, type: Boolean, default: false })
  private noGutter!: boolean;

  @Prop({ required: false, type: Boolean, default: false })
  private fullscreen!: boolean;

  @Prop({ required: false, type: Boolean, default: true })
  private retainFocus!: boolean;

  private isOpen = false;

  created() {
    this.isOpen = this.value;
  }

  private handleCancel(): void {
    if (this.isSending) return;
    this.isOpen = false;
    if (this.onCancel) this.onCancel();
  }

  private handleConfirm(): void {
    this.isOpen = false;
    if (this.onConfirm) this.onConfirm();
  }

  @Watch("value")
  private onValueChange(newVal: boolean): void {
    this.isOpen = newVal;
  }

  @Watch("isOpen")
  private onIsOpenChange(newVal: boolean): void {
    this.$emit("input", newVal);
  }
}
</script>

<style lang="scss" scoped>
::v-deep .modal {
  display: flex;
}

.card {
  display: flex;
  flex-direction: column;
}

.card-text {
  overflow-y: auto;
}
</style>
