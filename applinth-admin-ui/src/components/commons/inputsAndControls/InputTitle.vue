<template>
  <div class="input-title mb-1">
    <span class="subtitle-2 pr-1">
      <slot />
    </span>
    <Tooltip v-if="tooltip" :text="tooltip" />
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import Tooltip from "../Tooltip.vue";

@Component({
  components: {
    Tooltip,
  },
})
export default class InputTitle extends Vue {
  @Prop({ required: false, type: String })
  private tooltip?: string;
}
</script>

<style lang="scss" scoped>
.input-title {
  display: flex;
  align-items: flex-end;
}
</style>
