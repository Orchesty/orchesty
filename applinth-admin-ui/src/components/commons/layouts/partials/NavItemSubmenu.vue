<template>
  <div
    v-if="items.filter((item) => item.show === undefined || item.show).length"
  >
    <v-list-group
      @click="updateUserSettings"
      v-if="!navMiniVariant"
      :value="open"
    >
      <template v-slot:activator>
        <v-list-item-icon>
          <v-icon>{{ icon }}</v-icon>
        </v-list-item-icon>
        <v-list-item-title>
          {{ label }}
        </v-list-item-title>
      </template>

      <template v-for="(item, index) in items">
        <v-list-item
          v-if="item.show === undefined || item.show"
          :to="item.to"
          :key="index"
        >
          <v-list-item-title> {{ item.label }} </v-list-item-title>
        </v-list-item>
      </template>
    </v-list-group>

    <v-menu
      v-else
      v-model="open"
      :close-on-content-click="true"
      :nudge-width="200"
      offset-x
    >
      <template v-slot:activator="{ on, attrs }">
        <v-list-item v-bind="attrs" v-on="on">
          <v-tooltip :disabled="!navMiniVariant" right>
            <template v-slot:activator="{ on, attrs }">
              <v-list-item-icon v-bind="attrs" v-on="on">
                <v-icon>{{ icon }}</v-icon>
              </v-list-item-icon>
            </template>
            <span>{{ label }}</span>
          </v-tooltip>
          <v-list-item-title />
        </v-list-item>
      </template>
      <v-card>
        <v-list dense>
          <v-list-item-group>
            <v-list-item
              :to="item.to"
              v-for="(item, index) in items"
              :key="index"
            >
              <v-list-item-title> {{ item.label }} </v-list-item-title>
            </v-list-item>
          </v-list-item-group>
        </v-list>
      </v-card>
    </v-menu>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import { Location } from "vue-router";
import { userSettings } from "../../../../utils/userSettings";

@Component
export default class NavItemSubmenu extends Vue {
  @Prop({ required: true, type: Boolean })
  private navMiniVariant!: boolean;

  @Prop({ required: true, type: String })
  private label!: string;

  @Prop({ required: true, type: String })
  private icon!: string;

  @Prop({ required: true, type: Array })
  private items!: { show?: boolean; label: string; to: Location }[];

  private open: boolean;

  constructor() {
    super();
    if (!this.navMiniVariant) {
      this.open = userSettings.getNavSubmenuExpanded(this.label);
    } else {
      this.open = false;
    }
  }

  private updateUserSettings(): void {
    const prevVal = this.open;
    if (!this.navMiniVariant) {
      userSettings.updateNavSubmenuExpanded(this.label, !prevVal);
    }
  }
}
</script>

<style lang="scss" scoped></style>
