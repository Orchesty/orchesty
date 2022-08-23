<template>
  <v-data-table :headers="headers" :items="items" class="elevation-1">
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

  editItem(item: any) {
    this.$emit("edit", item);
  }

  deleteItem(item: any) {
    this.$emit("delete", item);
  }
}
</script>

<style lang="scss" scoped></style>
