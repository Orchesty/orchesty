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
  created: string
  updated: string
}

export interface TopologyDetail {
  _id: string
  type: 'cron' | 'webhook'
  name: string
  descr: string
  status: 'New' | 'Starting' | 'Running' | 'Stopped'
  visibility: 'draft' | 'public'
  version: string
  category: string | null
  enabled: boolean
  versions: TopologyVersion[] // All versions of this topology
}

