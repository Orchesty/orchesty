// Types for Topologies Page

export interface TopologyItem {
  id: string
  type: 'topology'
  name: string
  folderId?: string | null
  versionCount?: number // Number of versions, used to determine if modal should show
  enabled?: boolean
  visibility?: 'draft' | 'public'
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
  status: string
  visibility: 'draft' | 'public'
  version: number
  category: string | null
  enabled: boolean
  cronSettings: Array<{ cron: string; cronParams: string }>
}

export interface CronNode {
  id: string
  label: string
  name: string
  crontab: string
  enabled: boolean
  nextRun: string
}
