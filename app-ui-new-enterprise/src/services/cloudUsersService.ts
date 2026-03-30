import api from '@/services/api'

export interface CloudAccountUser {
  id: string
  email: string
  name: string
  role: string
}

export async function searchAccountUsers(query: string = ''): Promise<CloudAccountUser[]> {
  const response = await api.get<{ users: CloudAccountUser[] }>('/api/cloud/account-users', {
    params: { q: query },
  })
  return response.data.users
}

export async function addUserFromAccount(email: string, name?: string): Promise<{ email: string; added: boolean }> {
  const response = await api.post<{ email: string; added: boolean }>('/api/user/add-from-account', { email, name })
  return response.data
}
