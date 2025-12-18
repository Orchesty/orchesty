import type { Token, TokenQueryParams, TokenScope } from '@/types/settings'
import tokensDataJson from '@/assets/mock-data/tokens-data.json'
import tokenScopesDataJson from '@/assets/mock-data/token-scopes-data.json'

// Mock data
let tokensData = [...tokensDataJson.data] as Token[]
const availableScopes = tokenScopesDataJson.scopes as TokenScope[]

/**
 * Fetch tokens with pagination and filtering
 */
export async function fetchTokens(params: TokenQueryParams = {}) {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 300))

  const {
    page = 1,
    perPage = 10,
    sortBy = 'created',
    sortOrder = 'desc',
    search = '',
  } = params

  // Filter by search
  let filteredData = [...tokensData]
  if (search) {
    const searchLower = search.toLowerCase()
    filteredData = filteredData.filter((token) =>
      token.name.toLowerCase().includes(searchLower)
    )
  }

  // Sort
  filteredData.sort((a, b) => {
    const aValue = String(a[sortBy as keyof Token] || '')
    const bValue = String(b[sortBy as keyof Token] || '')
    const comparison = aValue.localeCompare(bValue)
    return sortOrder === 'asc' ? comparison : -comparison
  })

  // Paginate
  const totalItems = filteredData.length
  const totalPages = Math.ceil(totalItems / perPage)
  const startIndex = (page - 1) * perPage
  const endIndex = startIndex + perPage
  const paginatedData = filteredData.slice(startIndex, endIndex)

  return {
    data: paginatedData,
    meta: {
      page,
      perPage,
      totalItems,
      totalPages,
    },
  }
}

/**
 * Create a new token
 */
export async function createToken(data: {
  name: string
  expiration: string | null
  scopes: string[]
}): Promise<Token> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 500))

  // Generate a mock JWT token
  const tokenValue = `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.${btoa(
    JSON.stringify({
      sub: `token-${Date.now()}`,
      name: data.name,
      scopes: data.scopes,
      iat: Math.floor(Date.now() / 1000),
    })
  )}.${btoa(Math.random().toString(36).substring(2))}`

  const newToken: Token = {
    id: `token-${Date.now()}`,
    name: data.name,
    created: new Date().toISOString(),
    expiration: data.expiration,
    scopes: data.scopes,
    tokenValue,
  }

  tokensData.push(newToken)

  return newToken
}

/**
 * Delete a token
 */
export async function deleteToken(id: string): Promise<void> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 400))

  const index = tokensData.findIndex((t) => t.id === id)
  if (index === -1) {
    throw new Error(`Token with id ${id} not found`)
  }

  tokensData.splice(index, 1)
}

/**
 * Fetch available token scopes
 */
export async function fetchAvailableScopes(): Promise<TokenScope[]> {
  // Simulate API delay
  await new Promise((resolve) => setTimeout(resolve, 200))

  return availableScopes
}

