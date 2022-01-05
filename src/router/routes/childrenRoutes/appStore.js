export const APP_STORE = {
  DEFAULT: 'app-store',
  AVAILABLE_APPS: 'available-apps',
  INSTALLED_APPS: 'installed-apps',
  INSTALLED_APP: 'INSTALLED_APP',
  DETAIL_APP: 'DETAIL_APP',
}

export default [
  {
    path: '',
    name: APP_STORE.DEFAULT,
    component: () => import('../../../components/app/appStore/availableApp/AvailableAppsGridHandler'),
    meta: { title: 'Available Apps' },
  },
  {
    path: 'available-apps',
    name: APP_STORE.AVAILABLE_APPS,
    component: () => import('../../../components/app/appStore/availableApp/AvailableAppsGridHandler'),
    meta: { title: 'Available Apps' },
  },
  {
    path: 'installed-apps',
    name: APP_STORE.INSTALLED_APPS,
    component: () => import('../../../components/app/appStore/installedApp/InstalledAppsGridHandler'),
    meta: { title: 'Installed Apps' },
  },
  {
    path: 'installed/:key',
    component: () => import('../../../components/app/appStore/installedApp/InstalledApp'),
    name: APP_STORE.INSTALLED_APP,
    meta: { title: 'Installed App' },
  },
  {
    path: 'detail/:key',
    component: () => import('../../../components/app/appStore/availableApp/AvailableApp'),
    name: APP_STORE.DETAIL_APP,
    meta: { title: 'Detail App' },
  },
]
