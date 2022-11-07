<template>
  <tooltip orientation="right">
    <template #activator="{ on, attrs }">
      <v-list-item
        active-class="navigation-item-active"
        v-bind="attrs"
        :to="{ name: to }"
        v-on="on"
        @click="onClick"
      >
        <v-list-item-content>
          <app-icon>
            {{ icon }}
          </app-icon>
        </v-list-item-content>
      </v-list-item>
    </template>
    <template #tooltip>
      {{ tooltip }}
    </template>
  </tooltip>
</template>

<script>
import Tooltip from "@/components/commons/Tooltip"
import AppIcon from "@/components/commons/icon/AppIcon"
export default {
  name: "NavigationItem",
  components: { AppIcon, Tooltip },
  props: {
    to: {
      type: String,
      required: true,
    },
    icon: {
      type: String,
      required: true,
    },
    tooltip: {
      type: String,
      required: true,
    },
    onClick: {
      type: Function,
      default: () => {},
    },
  },
}
</script>

<style scoped lang="scss">
.v-list-item {
  height: 64px;
}

.navigation-item-active {
  background-color: var(--v-primary-base);

  .v-icon {
    color: var(--v-white-base) !important;
  }
}
</style>
