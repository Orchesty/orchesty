export interface User {
  id: string
  email: string
  created: string
}

export interface UserApiResponse {
  items: UserApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  filter: unknown[]
  search: string | null
  sorter: unknown[]
}

export interface UserApiItem {
  id: string
  email: string
  created: string
}

export interface UserApiFilter {
  search: string | null
  filter: Array<Array<{ column: string; operator: string; value: unknown[] }>>
  sorter: Array<{ column: string; direction: string }>
  paging: {
    itemsPerPage: number
    page: number
  }
}

export type InvitedUser = User

export interface InvitedUserApiResponse {
  items: UserApiItem[]
  paging: {
    itemsPerPage: number
    lastPage: number
    nextPage: number
    page: number
    previousPage: number
    total: number
  }
  filter: unknown[]
  search: string | null
  sorter: unknown[]
}

export interface GroupRule {
  resource: string
  actions: string[]
  propertyMask: number
}

export interface GroupUser {
  id: string
  email: string
}

export interface Group {
  id: string
  name: string
  level: number
  usersCount: number
  rules: GroupRule[]
  users?: GroupUser[]
}

export interface GroupListResponse {
  items: Group[]
  total: number
}

export interface GroupQueryParams {
  page?: number
  limit?: number
  sort?: string
  order?: string
  search?: string
}

