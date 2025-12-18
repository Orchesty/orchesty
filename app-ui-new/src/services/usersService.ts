import type { User, UserQueryParams, UserRole } from '@/types/users'
import usersDataJson from '@/assets/mock-data/users-data.json'

// Simulated API delay
const delay = (ms: number) => new Promise((resolve) => setTimeout(resolve, ms))

export async function fetchUsers(params: UserQueryParams): Promise<{
  data: User[]
  meta: {
    total: number
    totalPages: number
    currentPage: number
    perPage: number
  }
}> {
  await delay(300)

  let filteredData = [...(usersDataJson.data as User[])]

  // Filter by status
  if (params.status) {
    filteredData = filteredData.filter((user) => user.status === params.status)
  }

  // Filter by search (name or email)
  if (params.search) {
    const searchLower = params.search.toLowerCase()
    filteredData = filteredData.filter(
      (user) =>
        user.name.toLowerCase().includes(searchLower) ||
        user.email.toLowerCase().includes(searchLower)
    )
  }

  // Sort
  if (params.sortBy) {
    filteredData.sort((a, b) => {
      const aValue = a[params.sortBy as keyof User]
      const bValue = b[params.sortBy as keyof User]
      
      if (typeof aValue === 'string' && typeof bValue === 'string') {
        return params.sortOrder === 'asc'
          ? aValue.localeCompare(bValue)
          : bValue.localeCompare(aValue)
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

export async function fetchUserDetail(id: string): Promise<User | null> {
  await delay(200)

  const user = (usersDataJson.data as User[]).find((u) => u.id === id)
  return user || null
}

export async function inviteUsers(emails: string[]): Promise<{ success: boolean; message: string }> {
  await delay(500)

  console.log('Inviting users:', emails)
  return {
    success: true,
    message: `Successfully sent invitations to ${emails.length} user(s)`
  }
}

export async function updateUserRole(
  id: string,
  role: UserRole,
  groups: string[]
): Promise<{ success: boolean; message: string }> {
  await delay(400)

  console.log(`Updating user ${id}:`, { role, groups })
  return {
    success: true,
    message: 'User updated successfully'
  }
}

export async function removeUser(id: string): Promise<{ success: boolean; message: string }> {
  await delay(400)

  console.log('Removing user:', id)
  return {
    success: true,
    message: 'User removed successfully'
  }
}

