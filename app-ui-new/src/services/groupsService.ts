import type { Group, GroupQueryParams } from '@/types/users'
import groupsDataJson from '@/assets/mock-data/groups-data.json'

// Simulated API delay
const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

export async function fetchGroups(params: GroupQueryParams): Promise<{
  data: Group[]
  meta: {
    total: number
    totalPages: number
    currentPage: number
    perPage: number
  }
}> {
  await delay(300)

  let filteredData = [...(groupsDataJson.data as Group[])]

  // Filter by search (group name)
  if (params.search) {
    const searchLower = params.search.toLowerCase()
    filteredData = filteredData.filter((group) =>
      group.name.toLowerCase().includes(searchLower)
    )
  }

  // Sort
  if (params.sortBy) {
    filteredData.sort((a, b) => {
      const aValue = a[params.sortBy as keyof Group]
      const bValue = b[params.sortBy as keyof Group]
      
      if (typeof aValue === 'string' && typeof bValue === 'string') {
        return params.sortOrder === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue)
      }
      
      if (typeof aValue === 'number' && typeof bValue === 'number') {
        return params.sortOrder === 'asc' ? aValue - bValue : bValue - aValue
      }

      if (Array.isArray(aValue) && Array.isArray(bValue)) {
        return params.sortOrder === 'asc'
          ? aValue.length - bValue.length
          : bValue.length - aValue.length
      }
      
      return 0
    })
  }

  // Pagination
  const page = params.page || 1
  const perPage = params.perPage || 10
  const startIndex = (page - 1) * perPage
  const endIndex = startIndex + perPage
  const paginatedData = filteredData.slice(startIndex, endIndex)

  return {
    data: paginatedData,
    meta: {
      total: filteredData.length,
      totalPages: Math.ceil(filteredData.length / perPage),
      currentPage: page,
      perPage
    }
  }
}

export async function fetchGroupDetail(id: string): Promise<Group | null> {
  await delay(200)

  const group = (groupsDataJson.data as Group[]).find((g) => g.id === id)
  return group || null
}

export async function createGroup(data: {
  name: string
  modules: string[]
}): Promise<{ success: boolean; message: string; group?: Group }> {
  await delay(500)

  const newGroup: Group = {
    id: `group-${Date.now()}`,
    name: data.name,
    modules: data.modules,
    users: []
  }

  return {
    success: true,
    message: 'Group created successfully',
    group: newGroup
  }
}

export async function updateGroup(
  id: string,
  data: Partial<Group>
): Promise<{ success: boolean; message: string }> {
  await delay(400)

  return {
    success: true,
    message: 'Group updated successfully'
  }
}

export async function removeGroup(id: string): Promise<{ success: boolean; message: string }> {
  await delay(400)

  return {
    success: true,
    message: 'Group removed successfully'
  }
}

export async function addUserToGroup(
  groupId: string,
  userId: string
): Promise<{ success: boolean; message: string }> {
  await delay(300)

  return {
    success: true,
    message: 'User added to group successfully'
  }
}

export async function removeUserFromGroup(
  groupId: string,
  userId: string
): Promise<{ success: boolean; message: string }> {
  await delay(300)

  return {
    success: true,
    message: 'User removed from group successfully'
  }
}

