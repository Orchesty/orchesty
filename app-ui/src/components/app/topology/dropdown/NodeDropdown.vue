<template>
  <v-menu
    v-if="data"
    :value="!!data"
    offset-y
    :position-x="data.x"
    :position-y="data.y"
    :close-on-content-click="false"
    @input="$emit('clickAway')"
  >
    <div class="context-actions-wrapper pa-4">
      <div class="d-flex justify-space-between align-center mb-2">
        <span class="font-weight-bold">
          {{ data.name }}
        </span>

        <div class="d-flex align-center justify-center">
          <v-switch
            :input-value="data.enabled"
            hide-details
            class="mt-0 pt-0"
            color="info"
            :title="data.enabled ? $t('button.disable') : $t('button.enable')"
            @change="() => $emit('toggleStatus')"
          />
        </div>
      </div>
      <div class="mb-5">
        <a
          href=""
          class="action-link info--text font-weight-bold"
          :class="{
            disabled: !data.isRunnable,
            'text--disabled': !data.isRunnable,
          }"
          @click.prevent="runTopology"
        >
          {{ $t("button.runProcess") }}
        </a>
      </div>
      <slot name="default"></slot>
    </div>
  </v-menu>
</template>

<script>
export default {
  name: "NodeDropdown",
  props: {
    data: {
      type: Object,
      default: () => {},
    },
  },
  methods: {
    runTopology() {
      if (this.data.isRunnable) {
        this.$emit("runTopology")
      }
    },
  },
}
</script>

<style scoped lang="scss">
.context-actions-wrapper {
  background-color: var(--v-white-base);
  width: 50ch;
}

.action-link {
  &.disabled {
    text-decoration: none;
    pointer-events: none;
  }
}
</style>
