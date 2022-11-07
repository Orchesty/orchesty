<template>
  <div>
    <v-app-bar app color="primary" class="app-nav-bar">
      <router-link
        class="d-flex align-center white--text text-decoration-none"
        to="/"
      >
        <v-img
          alt="Applinth Logo"
          contain
          src="../../../assets/svg/applinth-logo.svg"
          width="40"
        />
        <span class="app-name ml-1"> {{ $t("appName") }}</span>
      </router-link>

      <v-spacer />

      <navigation-item
        v-for="item in navigationItems"
        :key="item.text"
        class="navigation-item navigation-item-with-style mr-md-2"
        :text="item.text"
        :icon="item.icon"
        :to="item.to"
      />
      <a
        href="#"
        class="d-flex align-center ml-1 navigation-item navigation-item-with-style"
        @click.prevent="onLogout"
      >
        <v-icon color="white"> mdi-logout </v-icon>
        <span class="white--text ml-1">
          {{ $t("navigation.link.logout") }}
        </span>
      </a>
    </v-app-bar>

    <v-main>
      <v-container>
        <v-row>
          <v-col>
            <v-breadcrumbs :items="breadCrumbs" class="px-0">
              <template #item="{ item }">
                <v-breadcrumbs-item :to="item.to" :exact="true">
                  {{ $t(item.text) }}
                </v-breadcrumbs-item>
              </template>
            </v-breadcrumbs>
          </v-col>
        </v-row>
        <slot />
      </v-container>
    </v-main>
  </div>
</template>

<script lang="ts">
import { Component, Prop, Vue } from "vue-property-decorator"
import NavigationItem from "@/components/app/NavigationItem.vue"
import { Routes } from "@/enums"
import { Action, Getter } from "vuex-class"
import {
  AuthActions,
  AuthGetters,
  authNamespace,
  User,
} from "@/store/modules/auth"
import Button from "@/components/commons/inputsAndControls/Button.vue"

type NavigationItemType = {
  to: string
  icon: string
  text: string
}
@Component({
  components: { Button, NavigationItem },
})
export default class AppLayout extends Vue {
  @Prop({ type: String, required: false })
  detailPageTitle: string | undefined = undefined

  @Getter(`${authNamespace}/${AuthGetters.GetUser}`)
  currentUser!: User

  @Action(`${authNamespace}/${AuthActions.Logout}`)
  private logout!: () => Promise<void>

  navigationItems: NavigationItemType[] = []

  get breadCrumbs() {
    if (this.$route.meta) {
      if (typeof this.$route.meta.breadcrumbs === "function") {
        return this.$route.meta.breadcrumbs(this.detailPageTitle)
      }
      return this.$route.meta.breadcrumbs
    } else {
      return null
    }
  }

  async onLogout() {
    await this.logout()
  }

  created() {
    this.navigationItems = [
      {
        to: Routes.Overview,
        icon: "mdi-format-list-bulleted",
        text: "navigation.link.overview",
      },
      {
        to: Routes.Customers,
        icon: "mdi-face-agent",
        text: "navigation.link.customers",
      },
      {
        to: Routes.Users,
        icon: "mdi-account-multiple",
        text: "navigation.link.users",
      },
      {
        to: Routes.Profile,
        icon: "mdi-account",
        text: (this.currentUser.name || this.currentUser.email) as string,
      },
    ]
  }
}
</script>

<style lang="scss" scoped>
.app-name {
  font-weight: bold;
  font-size: large;
}

.app-nav-bar {
  .navigation-item-with-style {
    padding: 8px;
    border-radius: 8px;
    transition: 0.3s;

    &:hover {
      background: rgb(122, 122, 122, 0.3);
    }

    &.router-link-active {
      background: rgb(155, 155, 155, 0.3);
    }
  }

  ::v-deep .v-toolbar__content {
    flex-wrap: wrap;
  }

  @media (max-width: 800px) {
    height: auto !important;
    min-height: 56px;

    ::v-deep.v-toolbar__content {
      height: auto !important;
      max-height: 128px;
      min-height: 56px;
    }
  }
}

main {
  @media (max-width: 425px) {
    padding-top: 128px !important;
  }
}
</style>
