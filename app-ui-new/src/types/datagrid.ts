export interface ActionConfig {
  icon: 'search' | 'edit' | 'delete' | 'download' | 'more'
  title: string
  onClick: (row: Record<string, any>) => void
  show?: (row: Record<string, any>) => boolean
}

export interface QuickFilterOption {
  value: string
  label: string
}

export interface DropdownFilterOption {
  value: string | null
  label: string
}

