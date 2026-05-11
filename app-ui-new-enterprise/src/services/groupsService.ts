import type { Group, GroupListResponse } from '@/types/users'
import api from './api'

export async function fetchGroups(): Promise<GroupListResponse> {
  const response = await api.get<GroupListResponse>('/api/group/list')
  return response.data
}

export async function fetchGroupDetail(id: string): Promise<Group> {
  const response = await api.get<Group>(`/api/group/${id}`)
  return response.data
}

export async function createGroup(name: string): Promise<Group> {
  const response = await api.post<Group>('/api/group', { name })
  return response.data
}

export async function updateGroup(
  id: string,
  data: { name?: string },
): Promise<Group> {
  const response = await api.put<Group>(`/api/group/${id}`, data)
  return response.data
}

export async function removeGroup(id: string): Promise<void> {
  await api.delete(`/api/group/${id}`)
}

export async function addUserToGroup(groupId: string, userId: string): Promise<void> {
  await api.post(`/api/group/${groupId}/user/${userId}`)
}

export async function removeUserFromGroup(groupId: string, userId: string): Promise<void> {
  await api.delete(`/api/group/${groupId}/user/${userId}`)
}

export async function fetchUserGroups(userId: string): Promise<GroupListResponse> {
  const response = await api.get<GroupListResponse>(`/api/user/${userId}/groups`)
  return response.data
}

export async function fetchMyGroups(): Promise<GroupListResponse> {
  const response = await api.get<GroupListResponse>('/api/user/me/groups')
  return response.data
}

export type PermissionsSchema = Record<string, string[]>

export interface RulePayload {
  resource: string
  actions: string[]
}

export interface PresetDefinition {
  name: string
  label: string
  description: string
  level: number
  groupId: string | null
  rules: RulePayload[]
}

export async function fetchPermissionsSchema(): Promise<PermissionsSchema> {
  const response = await api.get<PermissionsSchema>('/api/permissions/schema')
  return response.data
}

export async function fetchPresets(): Promise<PresetDefinition[]> {
  const response = await api.get<PresetDefinition[]>('/api/permissions/presets')
  return response.data
}

export async function ensurePresetGroups(): Promise<void> {
  await api.post('/api/permissions/ensure-presets')
}

export async function setUserRole(userId: string, role: string): Promise<void> {
  await api.put(`/api/user/${userId}/role`, { role })
}

export interface TopologyListItem {
  id: string
  name: string
}

export interface TopologyAccessEntry {
  groupId: string
  groupName: string
  actions: string[]
}

export async function fetchTopologyAccess(topologyId: string): Promise<TopologyAccessEntry[]> {
  const response = await api.get<TopologyAccessEntry[]>(`/api/topologies/${topologyId}/access`)
  return response.data
}

export async function updateTopologyAccess(
  topologyId: string,
  access: { groupId: string; actions: string[] }[],
): Promise<TopologyAccessEntry[]> {
  const response = await api.put<TopologyAccessEntry[]>(`/api/topologies/${topologyId}/access`, {
    access,
  })
  return response.data
}

export async function fetchTopologyList(): Promise<TopologyListItem[]> {
  const response = await api.get<{
    items: Array<{ _id: string; name: string; version: number }>
  }>('/api/topologies')

  const byName = new Map<string, { id: string; name: string; version: number }>()
  for (const item of response.data.items) {
    const existing = byName.get(item.name)
    if (!existing || item.version > existing.version) {
      byName.set(item.name, { id: item._id, name: item.name, version: item.version })
    }
  }

  return Array.from(byName.values())
    .map(({ id, name }) => ({ id, name }))
    .sort((a, b) => a.name.localeCompare(b.name))
}
