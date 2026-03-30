import type { Component } from 'vue'

export interface SidebarItem {
  id: string
  label: string
  path: string
  icon: Component
  iconStrokeWidth?: number
  iconSizeClass?: string
  badge?: 'cron-alerts'
  insertAfter?: string
}

export interface NavbarMenuItem {
  type: 'link' | 'custom'
  label?: string
  to?: string
  slotName?: string
}

export interface NavbarMenuSection {
  header?: { title: string; subtitle: string }
  items: NavbarMenuItem[]
}
