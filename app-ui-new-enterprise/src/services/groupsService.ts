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

export async function createGroup(name: string, level: number = 999): Promise<Group> {
  const response = await api.post<Group>('/api/group', { name, level })
  return response.data
}

export async function updateGroup(
  id: string,
  data: { name?: string; level?: number },
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
