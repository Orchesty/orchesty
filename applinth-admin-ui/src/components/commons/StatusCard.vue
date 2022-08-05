<template>
  <div class="status" :class="{ error: hasError }">
    <div class="status-info">
      <div class="card-title">{{ score }}</div>
      <div class="card-subtitle">{{ title }}</div>
    </div>
    <div v-if="badge" class="badge">{{ badge }}</div>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";

@Component
export default class StatusCard extends Vue {
  @Prop({ type: Number, default: null })
  readonly score!: number;

  @Prop({ type: String, default: "" })
  readonly title!: string;

  @Prop({ type: Boolean, default: false })
  readonly hasError!: boolean;

  @Prop({ type: Number, default: null })
  readonly badge!: number;
}
</script>

<style lang="scss" scoped>
//TODO Nebude lepsi pouzit clamp typografie obecne nez to pouzivat jen u nejakych kompoenet?
.card-title {
  font-size: clamp(1rem, 3vw, 40px);
  font-weight: 600;
}

.card-subtitle {
  font-size: clamp(0.8rem, 1.5vw, 20px);
  text-transform: uppercase;
}

.status {
  background: rgb(2, 48, 71);
  color: white;
  display: grid;
  place-items: center;
  border-radius: 13px;
  padding: 15px 20px;
  text-align: center;
  position: relative;
  box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.25);
}

.badge {
  position: absolute;
  top: 10px;
  right: 15px;
  background: red;
  display: flex;
  justify-content: center;
  align-items: center;
  border-radius: 50%;
  padding: 5px;
  width: 30px;
  height: 30px;
  font-weight: bold;
}

.error {
  background: $color-red;
}
</style>
