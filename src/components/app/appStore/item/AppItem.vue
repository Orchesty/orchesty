<template>
  <v-col xl="2" lg="3" md="6" sm="6" cols="12">
    <v-card class="py-5" rounded="lg" elevation="2" min-height="205">
      <div class="mx-4">
        <v-row>
          <v-col cols="6" sm="5">
            <v-img max-height="70" max-width="70" contain :src="imageSource" />
          </v-col>
          <v-col cols="6" sm="7" class="pr-0">
            <div class="d-flex flex-column auto-margin align-end mr-2">
              <slot name="redirect"></slot>
            </div>
          </v-col>
        </v-row>
        <v-card-title class="px-0 pb-1">
          <span class="custom-truncate-title font-weight-bold">
            {{ title }}
          </span>
          <v-tooltip bottom>
            <template #activator="{ on }">
              <v-icon v-if="installed" size="15" class="ml-3" :color="authorized ? 'success' : 'error'" v-on="on">
                mdi-circle
              </v-icon>
            </template>
            <span>{{ authorized ? 'authorized' : 'unauthorized' }}</span>
          </v-tooltip>
        </v-card-title>
        <v-card-text class="px-0 py-0 custom-truncate-description">
          {{ description }}
        </v-card-text>
      </div>
    </v-card>
  </v-col>
</template>

<script>
export default {
  name: 'AppItem',
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
    linkDestination: {
      type: String,
      default: () => '',
    },
    authorized: {
      type: Boolean,
      required: true,
    },
    installed: {
      type: Boolean,
      required: false,
    },
  },
  computed: {
    imageSource() {
      return this.image ? this.image : require('@/assets/svg/app_placeholder.svg')
    },
  },
}
</script>

<style lang="scss" scoped>
//.auto-margin > .app-item-redirect {
//  margin-left: auto;
//  margin-right: 1em;
//  @media #{map-get($display-breakpoints, 'sm-and-down')} {
//    margin-left: 0;
//    margin-right: 0;
//  }
//  &:not(:last-child) {
//    margin-bottom: 10px;
//  }
//}

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
