<template>
  <div>
    <v-container fluid>
      <v-tabs class="tabs">
        <v-tab
          @change="$emit('change')"
          v-for="(item, key) in items"
          :key="key"
          :to="{ name: item.to }"
          exact
        >
          {{ item.title }}
        </v-tab>
      </v-tabs>
    </v-container>
    <router-view :data="data" />
  </div>
</template>

<script lang="ts">
import { Prop, Component, Vue } from "vue-property-decorator"

export type TabItem = {
  title: string
  to: string
}

@Component
export default class TabRouter extends Vue {
  @Prop({ type: Array, required: true })
  private items!: Array<TabItem>

  @Prop({ type: Object, default: () => ({}) })
  private data!: object
}
</script>

<style lang="scss" scoped>
$tab-font-weight: map.get($headings, "h2", "weight");
$tab-font-size: map.get($headings, "h2", "size");
$tab-letter-spacing: map.get($headings, "h2", "letter-spacing");
$tabs-item-padding: 0 10px;

.tabs ::v-deep .v-tab {
  font-weight: $tab-font-weight;
  font-size: $tab-font-size;
  letter-spacing: $tab-letter-spacing;
  padding: $tabs-item-padding;
}
</style>
