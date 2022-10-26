<template>
  <div class="status" :class="{ error: hasError }">
    <v-progress-circular
      class="mb-2"
      size="20"
      v-if="loading"
      indeterminate
      color="white"
    />
    <SubHeading v-else>{{ score }}</SubHeading>
    <span>{{ title }}</span>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import SubHeading from "@/components/commons/typography/SubHeading.vue"
@Component({
  components: { SubHeading },
})
export default class StatusCard extends Vue {
  @Prop({ type: [Number, String], default: null })
  readonly score!: number | string

  @Prop({ type: String, default: "" })
  readonly title!: string

  @Prop({ type: Boolean, default: false })
  readonly hasError!: boolean

  @Prop({ type: Boolean, default: false })
  readonly loading!: boolean

  @Prop({ type: Number, default: null })
  readonly badge!: number
}
</script>

<style lang="scss" scoped>
.status {
  background: $primary;
  color: white;
  display: grid;
  place-items: center;
  border-radius: 13px;
  padding: 15px 20px;
  text-align: center;
  position: relative;
  box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.25);
}
</style>
