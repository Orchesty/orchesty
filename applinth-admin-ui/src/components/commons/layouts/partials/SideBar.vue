<template>
  <div class="sidebar">
    <div class="logo-wrapper py-8">
      <router-link :to="{ name: routes.Dashboard }" class="link">
        <img src="@/assets/img/app-logo.png" class="responsive-image" />
      </router-link>
    </div>
    <ul class="nav-items py-6 d-flex direction-column gap-6">
      <li>
        <SideBarItem
          text="Dashboard"
          :to="{ name: routes.Dashboard }"
          iconName="dashboard"
          stroke
        />
      </li>
      <li>
        <SideBarItem
          text="Plánovač"
          :to="{ name: routes.Scheduler }"
          iconName="scheduler"
          stroke
        />
      </li>
      <li>
        <SideBarItem
          text="Laboranti"
          :to="{ name: routes.Laborers }"
          iconName="laborers"
          fill
        />
      </li>
      <li>
        <SideBarItem
          text="Uživatelé"
          :to="{ name: routes.Users }"
          iconName="users"
          fill
        />
      </li>
      <li>
        <SideBarItem
          text="Procesy"
          :to="{ name: routes.ProcessManagement }"
          iconName="processes"
          fill
        />
      </li>
      <li class="service-section">
        <SideBarItem class="mt-3 mb-5" text="Servis" />
        <SideBarItem
          text="Zařízení"
          :to="{ name: routes.Devices }"
          iconName="devices"
          fill
        />
      </li>
      <li>
        <SideBarItem
          text="Uskutečněný"
          :to="{ name: routes.MaintenancesCompleted }"
          iconName="service-done"
          fill
        />
      </li>
      <li>
        <SideBarItem
          text="Plánovaný"
          :to="{ name: routes.MaintenancesPlanned }"
          iconName="plus-circle-outline"
          fill
        />
      </li>
    </ul>
  </div>
</template>

<script lang="ts">
import { Component, Vue } from "vue-property-decorator";
import SideBarItem from "./SideBarItem.vue";
import { Routes } from "../../../../enums/Routes";

@Component({
  components: {
    SideBarItem,
  },
})
export default class SideBar extends Vue {
  routes = Routes;
}
</script>

<style lang="scss" scoped>
.sidebar {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.logo-wrapper {
  display: flex;
  justify-content: center;
  width: 100%;
  border-bottom: 0.5px solid $color-main-text-50;
}

.nav-items {
  list-style: none;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 1.5rem;
}

.link {
  width: 100%;
}

.service-section {
  border-top: 0.5px solid $color-main-text-50;
}
</style>
