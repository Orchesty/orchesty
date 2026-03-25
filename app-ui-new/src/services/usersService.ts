import type { User, InvitedUser, UserApiResponse, InvitedUserApiResponse, UserApiFilter, UserApiItem } from '@/types/users'
import api from './api'

function mapApiItemToUser(item: UserApiItem): User {
  return {
    id: item.id,
    email: item.email,
    created: item.created,
  }
}

export async function fetchUsers(params: {
  page?: number
  limit?: number
  sort?: string
  order?: string
  search?: string
}): Promise<{
  data: User[]
  meta: {
    total: number
    totalPages: number
    currentPage: number
    perPage: number
  }
}> {
  const filterObj: UserApiFilter = {
    search: params.search || null,
    filter: [],
    sorter: params.sort
      ? [{ column: params.sort, direction: (params.order || 'asc').toUpperCase() }]
      : [],
    paging: {
      itemsPerPage: params.limit || 10,
      page: params.page || 1,
    },
  }

  const response = await api.post<UserApiResponse>(
    `/api/user/list?filter=${encodeURIComponent(JSON.stringify(filterObj))}`,
  )

  return {
    data: response.data.items.map(mapApiItemToUser),
    meta: {
      total: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      perPage: response.data.paging.itemsPerPage,
    },
  }
}

export interface InviteResult {
  email: string
  hash?: string
  error?: string
}

export async function inviteUsers(emails: string[]): Promise<InviteResult[]> {
  const results: InviteResult[] = []

  for (const email of emails) {
    try {
      const response = await api.post<{ hash: string; email: string }>('/api/user/invite', { email })
      results.push({ email: response.data.email, hash: response.data.hash })
    } catch (error: unknown) {
      const message = error instanceof Error ? error.message : 'Failed to invite user'
      results.push({ email, error: message })
    }
  }

  return results
}

export async function removeUser(id: string): Promise<void> {
  await api.delete(`/api/user/${id}/delete`)
}

export async function fetchInvitedUsers(params: {
  page?: number
  limit?: number
  sort?: string
  order?: string
  search?: string
}): Promise<{
  data: InvitedUser[]
  meta: {
    total: number
    totalPages: number
    currentPage: number
    perPage: number
  }
}> {
  const filterObj: UserApiFilter = {
    search: params.search || null,
    filter: [],
    sorter: params.sort
      ? [{ column: params.sort, direction: (params.order || 'asc').toUpperCase() }]
      : [],
    paging: {
      itemsPerPage: params.limit || 10,
      page: params.page || 1,
    },
  }

  const response = await api.post<InvitedUserApiResponse>(
    `/api/user/invited/list?filter=${encodeURIComponent(JSON.stringify(filterObj))}`,
  )

  return {
    data: response.data.items.map(mapApiItemToUser),
    meta: {
      total: response.data.paging.total,
      totalPages: response.data.paging.lastPage,
      currentPage: response.data.paging.page,
      perPage: response.data.paging.itemsPerPage,
    },
  }
}

export async function regenerateInvite(id: string): Promise<{ hash: string; email: string }> {
  const response = await api.post<{ hash: string; email: string }>(`/api/user/invited/${id}/regenerate`)
  return response.data
}

export async function deleteInvitedUser(id: string): Promise<void> {
  await api.delete(`/api/user/invited/${id}/delete`)
}
