// Types for Topologies Page

export interface TopologyItem {
  id: string
  type: 'topology'
  name: string
  folderId?: string | null
  versionCount?: number // Number of versions, used to determine if modal should show
}

export interface FolderItem {
  id: string
  type: 'folder'
  name: string
  parentFolderId?: string | null
  isExpanded?: boolean
  children: TopologiesTreeNode[]
}

export type TopologiesTreeNode = TopologyItem | FolderItem

export interface TopologyVersion {
  id: string
  version: string // e.g., "0.1.0"
  visibility: 'draft' | 'public'
  status: 'New' | 'Starting' | 'Running' | 'Stopped'
  enabled: boolean
  created: string
  updated: string
}

export interface TopologyDetail {
  _id: string
  type: 'cron' | 'webhook'
  name: string
  description: string
  status: 'New' | 'Starting' | 'Running' | 'Stopped'
  visibility: 'draft' | 'public'
  version: string
  category: string | null
  enabled: boolean
  versions: TopologyVersion[] // All versions of this topology
  mcp_description?: Record<string, unknown>
}

import type { Ref } from 'vue'

export interface TopologyLayoutContext {
  openEditTopologyModal: (id: string, name: string, currentDescription?: string) => void
  openMoveTopologyModal: (id: string, name: string, currentCategoryId?: string | null) => void
  openDeleteTopologyConfirm: (id: string, name: string) => void
  handleRunTopologyAction: (id: string, name: string) => Promise<void>
  handleCloneTopologyAction: (id: string) => Promise<void>
  handleExportTopologyAction: (id: string, name: string) => Promise<void>
  refreshSidebar: () => Promise<void>
  onTopologyEdited: (callback: () => void) => void
  onTopologyMoved: (callback: () => void) => void
  // eslint-disable-next-line @typescript-eslint/no-explicit-any
  sidebarRef: Ref<any>
  topologySidebarCollapsed: Ref<boolean>
}

export interface CronNode {
  id: string
  label: string
  name: string
  crontab: string
  enabled: boolean
  nextRun: string
}