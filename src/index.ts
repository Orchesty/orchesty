// Config
export { coreRoutes } from './config/routes'
export type { SidebarItem, NavbarMenuItem, NavbarMenuSection } from './config/navigation'
export * from './config/topology'
export * from './config/dashboard'

// Router
export { createAppRouter, invalidateUsersExistCache } from './router'

// Stores
export { useAuthStore } from './stores/auth'

// Composables
export { useActivityTracker } from './composables/useActivityTracker'
export { useApexChart } from './composables/useApexChart'
export { useCopyToClipboard } from './composables/useCopyToClipboard'
export { useCronAlerts } from './composables/useCronAlerts'
export { useCronNodeActions } from './composables/useCronNodeActions'
export { useDarkMode } from './composables/useDarkMode'
export { useDashboardTimeSync } from './composables/useDashboardTimeSync'
export { useDataGrid } from './composables/useDataGrid'
export { useDateFormat } from './composables/useDateFormat'
export { useLastTopology } from './composables/useLastTopology'
export { useProcessPolling } from './composables/useProcessPolling'
export { useSidebar } from './composables/useSidebar'
export { useTabDataFreshness } from './composables/useTabDataFreshness'
export { useToast } from './composables/useToast'
export { useTopologyNodeFilter } from './composables/useTopologyNodeFilter'
export { useTopologyNodeMappings } from './composables/useTopologyNodeMappings'
export {
  useAuthorization,
  provideAuthorization,
  AUTHORIZATION_KEY,
  type AuthorizationProvider,
} from './composables/useAuthorization'
export { useHelp, provideHelp } from './composables/useHelp'
export { useSystemWorkers, SYSTEM_WORKERS_KEY } from './composables/useSystemWorkers'

// Services
export { default as api } from './services/api'
export * from './services/authService'
export * from './services/applicationsService'
export * from './services/breakpointService'
export * from './services/connectorsService'
export * from './services/dashboardService'
export * from './services/logsService'
export * from './services/processDetailService'
export * from './services/processesService'
export * from './services/scheduledTasksService'
export * from './services/tokensService'
export * from './services/topologiesService'
export * from './services/topologyEditorService'
export * from './services/topologyMetricsService'
export * from './services/trashService'
export * from './services/usersService'
export * from './services/workersService'
export * from './services/accountService'
export * from './services/helpService'

// Types
export type * from './types/api'
export type * from './types/applications'
export type {
  UserSettings,
  LoginRequest,
  LoginResponse,
  User as AuthUser,
} from './types/auth'
export type * from './types/connectors'
export type * from './types/dashboard'
export type * from './types/datagrid'
export type * from './types/logs'
export type * from './types/processes'
export type * from './types/scheduled-tasks'
export type * from './types/settings'
export type * from './types/toast'
export type * from './types/topologies-page'
export type * from './types/topologies'
export type * from './types/topology-metrics'
export type * from './types/trash'
export type * from './types/ui'
export type * from './types/users'
export type * from './types/account'

// Utils
export { cn } from './utils/cn'
export * from './utils/cronParser'
export * from './utils/crontabValidator'
export * from './utils/formatName'
export * from './utils/formatters'
export * from './utils/mcpManifestValidator'
export * from './utils/timeRangeConverter'

// UI Components
export { default as AttributeInput } from './components/ui/AttributeInput.vue'
export { default as Button } from './components/ui/Button.vue'
export { default as Card } from './components/ui/Card.vue'
export { default as Checkbox } from './components/ui/Checkbox.vue'
export { default as Confirm } from './components/ui/Confirm.vue'
export { default as CopyValue } from './components/ui/CopyValue.vue'
export { default as DataGrid } from './components/ui/DataGrid.vue'
export { default as Drawer } from './components/ui/Drawer.vue'
export { default as DropdownMenu } from './components/ui/DropdownMenu.vue'
export { default as FormInput } from './components/ui/FormInput.vue'
export { default as HorizontalBarChart } from './components/ui/HorizontalBarChart.vue'
export { default as Input } from './components/ui/Input.vue'
export { default as KeyValueInput } from './components/ui/KeyValueInput.vue'
export { default as LoadingSpinner } from './components/ui/LoadingSpinner.vue'
export { default as Modal } from './components/ui/Modal.vue'
export { default as MoreActions } from './components/ui/MoreActions.vue'
export type { MoreActionsSection, MoreActionsItem } from './components/ui/MoreActions.vue'
export { default as PasswordInput } from './components/ui/PasswordInput.vue'
export { default as SearchInput } from './components/ui/SearchInput.vue'
export { default as SidebarMoreActions } from './components/ui/SidebarMoreActions.vue'
export { default as StatusBadge } from './components/ui/StatusBadge.vue'
export { default as TabCard } from './components/ui/TabCard.vue'
export { default as TabPanel } from './components/ui/TabPanel.vue'
export { default as Tabs } from './components/ui/Tabs.vue'
export { default as TextInput } from './components/ui/TextInput.vue'
export { default as TimeFilter } from './components/ui/TimeFilter.vue'
export { default as TimeRangeFilter } from './components/ui/TimeRangeFilter.vue'
export { default as TimeRangeFilterWithCustomRange } from './components/ui/TimeRangeFilterWithCustomRange.vue'
export { default as Toast } from './components/ui/Toast.vue'

// Datagrid components
export { default as DataGridActions } from './components/ui/datagrid/DataGridActions.vue'
export { default as DateTimeRangeFilter } from './components/ui/datagrid/DateTimeRangeFilter.vue'
export { default as DropdownFilter } from './components/ui/datagrid/DropdownFilter.vue'
export { default as GridLink } from './components/ui/datagrid/GridLink.vue'
export { default as QuickFilter } from './components/ui/datagrid/QuickFilter.vue'
export { default as SearchableDropdownFilter } from './components/ui/datagrid/SearchableDropdownFilter.vue'
export { default as DataGridTextarea } from './components/ui/datagrid/Textarea.vue'
export { default as DataGridTextInput } from './components/ui/datagrid/TextInput.vue'

// Help components
export { default as HelpDrawer } from './components/help/HelpDrawer.vue'

// Layout components
export { default as AppNavbar } from './components/layout/AppNavbar.vue'
export { default as AppSidebar } from './components/layout/AppSidebar.vue'

// Layout views
export { default as AuthLayout } from './layouts/AuthLayout.vue'
export { default as DashboardLayout } from './layouts/DashboardLayout.vue'
export { default as DefaultLayout } from './layouts/DefaultLayout.vue'

// App component
export { default as App } from './App.vue'

// TopologyDetailView (with extension points)
export { default as TopologyDetailView } from './views/topologies/TopologyDetailView.vue'
export type { TopologyTab } from './views/topologies/TopologyDetailView.vue'
