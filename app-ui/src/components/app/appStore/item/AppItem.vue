<template>
  <v-col xl="2" lg="3" md="6" sm="6" cols="12">
    <v-card rounded="lg" elevation="2" min-height="205">
      <v-container fluid>
        <v-row>
          <v-col cols="6" sm="5">
            <v-img max-height="70" max-width="70" contain :src="imageSource" />
          </v-col>
          <v-col cols="6" sm="7" class="d-flex flex-column align-end justify-space-around">
            <slot name="redirect"></slot>
          </v-col>
        </v-row>
        <v-row>
          <v-col class="d-flex justify-start">
            <h3 class="custom-truncate-title title font-weight-bold">
              {{ title }}
            </h3>
            <tooltip>
              <template #activator>
                <app-icon v-if="installed" dense :color="authorized ? 'success' : 'error'" class="ml-3">
                  mdi-circle
                </app-icon>
              </template>
              <template #tooltip>
                {{ authorized ? 'authorized' : 'unauthorized' }}
              </template>
            </tooltip>
          </v-col>
        </v-row>
        <v-row>
          <v-col class="custom-truncate-description py-0">
            <span>{{ description }}</span>
          </v-col>
        </v-row>
      </v-container>
    </v-card>
  </v-col>
</template>

<script>
import Tooltip from '@/components/commons/Tooltip'
import AppIcon from '@/components/commons/icon/AppIcon'
export default {
  name: 'AppItem',
  components: { AppIcon, Tooltip },
  props: {
    title: {
      type: String,
      required: true,
    },
    image: {
      type: String,
      required: true,
    },
    description: {
      type: String,
      required: false,
      default: () => '',
    },
    authorized: {
      type: Boolean,
      default: false,
    },
    installed: {
      type: Boolean,
      required: false,
    },
  },
  computed: {
    imageSource() {
      return this.image ? this.image : require('@/assets/svg/app-item-placeholder.svg')
    },
  },
}
</script>

<style lang="scss" scoped>
.custom-truncate {
  display: -webkit-box;
  -webkit-box-orient: vertical;
  overflow: hidden;
}
.custom-truncate-description {
  @extend .custom-truncate;
  -webkit-line-clamp: 2;
}
.custom-truncate-title {
  @extend .custom-truncate;
  -webkit-line-clamp: 1;
  max-width: 88%;
}
</style>
