<template>
  <Modal
    v-model="isOpen"
    :title="title"
    :cancel-btn-text="$t('button.no')"
    :confirm-btn-text="$t('button.yes')"
    :on-confirm="() => onConfirm(payload)"
    :is-sending="isSending"
  >
    <slot v-bind="payload" />
  </Modal>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import Modal from "./Modal.vue";
import { eventBus } from "../../../utils/eventBus";
import { EventBus } from "../../../enums";

@Component({
  components: { Modal },
})
export default class ConfirmModal extends Vue {
  @Prop({ required: false, type: Boolean, default: false })
  private value!: boolean;

  @Prop({ required: true, type: String })
  private eventBusName!: EventBus;

  @Prop({ type: Function, required: true })
  private onConfirm!: { (val: any): Promise<boolean> };

  @Prop({ type: String, required: true })
  private title!: string;

  @Prop({ required: false, type: Boolean, default: false })
  private isSending!: boolean;

  private isOpen = false;
  private payload: any = null;

  created() {
    this.isOpen = this.value;
    eventBus.$on(this.eventBusName, (payload: any) => {
      this.isOpen = true;
      this.payload = payload;
    });
  }
}
</script>
