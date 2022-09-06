<template>
  <v-data-table
    :loading="loading"
    :headers="headers"
    :items="items"
    class="elevation-1"
  >
    <template v-for="item in headers" #[`header.${item.value}`]="{ header }">
      <span :key="item.value">{{ $t(header.text) }}</span>
    </template>
    <template v-slot:[`item.actions`]="{ item }">
      <ActionsWrapper>
        <slot name="actions" :item="item" />
      </ActionsWrapper>
    </template>
  </v-data-table>
</template>

<script lang="ts">
import ActionsWrapper from "@/components/commons/tables/ActionsWrapper.vue";
import { Vue, Prop, Component } from "vue-property-decorator";

@Component({
  components: {
    ActionsWrapper,
  },
})
export default class SimpleTable extends Vue {
  @Prop({ type: Array, required: true })
  readonly headers!: any[];

  @Prop({ type: Array, required: true })
  readonly items!: any[];

  @Prop({ type: Boolean, required: false, default: false })
  readonly loading!: boolean;

  editItem(item: any) {
    this.$emit("edit", item);
  }

  deleteItem(item: any) {
    this.$emit("delete", item);
  }
}
</script>

<style lang="scss" scoped></style>
