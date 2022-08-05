<template>
  <router-link v-if="to.name" class="link" :to="to">
    <SvgIcon
      v-if="iconName"
      :iconName="iconName"
      :stroke="stroke"
      :fill="fill"
    />
    <span>{{ text }}</span>
  </router-link>
  <div v-else class="link text">
    <span>{{ text }}</span>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator";
import SvgIcon from "../../../app/SvgIcon.vue";

@Component({
  components: {
    SvgIcon,
  },
})
export default class SideBarItem extends Vue {
  @Prop({ type: String, required: true })
  private text!: string;

  @Prop({ type: Object, default: () => ({}) })
  private to!: object;

  @Prop({ type: String, default: "" })
  private iconName!: string;

  @Prop({ type: Boolean, default: false })
  private fill!: boolean;

  @Prop({ type: Boolean, default: false })
  private stroke!: boolean;
}
</script>

<style lang="scss" scoped>
.link {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-decoration: none;
  color: $color-main-text;
  font-weight: 600;
  font-size: 0.75rem;

  &:hover {
    color: $color-accent-blue;
  }

  &.text:hover {
    color: $color-main-text;
  }

  &.router-link-active {
    color: $color-accent-blue;
  }
}
</style>
