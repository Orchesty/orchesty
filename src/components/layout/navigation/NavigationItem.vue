<template>
  <tooltip orientation="right">
    <template #activator="{ on, attrs }">
      <v-list-item v-bind="attrs" :to="{ name: to }" v-on="on" @click="onClick">
        <v-list-item-content>
          <v-icon> {{ icon }} </v-icon>
        </v-list-item-content>
      </v-list-item>
    </template>
    <template #tooltip>
      <span class="font-weight-medium"> {{ tooltip }} </span>
    </template>
  </tooltip>
</template>

<script>
import Tooltip from '@/components/commons/tooltip/Tooltip'
export default {
  name: 'NavigationItem',
  components: { Tooltip },
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

<style scoped>
.v-list-item {
  height: 64px;
}
</style>
