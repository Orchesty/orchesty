import api from './api'
import type {
  Token,
  TokenQueryParams,
  TokenScope,
  TokenApiResponse,
  TokensListResponse,
} from '@/types/settings'

// Convert scopes string to array
function scopesStringToArray(scopes: string | string[]): string[] {
  if (Array.isArray(scopes)) return scopes
  return scopes ? scopes.split(',').map(s => s.trim()) : []
}

// Map API response to component Token type
function mapApiTokenToToken(apiToken: TokenApiResponse): Token {
  return {
    id: apiToken.id,
    name: apiToken.user,  // Use user as name
    created: apiToken.created,
    expiration: apiToken.expireAt,
    scopes: scopesStringToArray(apiToken.scopes),
    tokenValue: apiToken.key,  // Only present on create
  }
}

/**
 * Fetch tokens with pagination and filtering
 */
export async function fetchTokens(params: TokenQueryParams = {}) {
  const {
    page = 1,
    perPage = 10,
    sortBy = 'created',
    sortOrder = 'desc',
    search = '',
  } = params

  const response = await api.get<TokensListResponse>('/api/apiTokens', {
    params: {
      page,
      itemsPerPage: perPage,
      ...(search && { search }),
      // Backend has its own sorting via sorter field
    },
  })

  const data = response.data

  return {
    data: data.items.map(mapApiTokenToToken),
    meta: {
      page: data.paging.page,
      perPage: data.paging.itemsPerPage,
      totalItems: data.paging.total,
      totalPages: data.paging.lastPage,
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
  // Backend doesn't accept 'name', only expireAt and scopes
  // Format expiration date to YYYY-MM-DD if provided
  const requestData = {
    expireAt: data.expiration,  // Already in correct format from datepicker
    scopes: data.scopes,
  }

  const response = await api.post<TokenApiResponse>('/api/apiTokens', requestData)

  return mapApiTokenToToken(response.data)
}

/**
 * Delete a token
 */
export async function deleteToken(id: string): Promise<void> {
  await api.delete(`/api/apiTokens/${id}`)
}

/**
 * Fetch available token scopes
 * Note: This might need a real endpoint - for now returning hardcoded common scopes
 */
export async function fetchAvailableScopes(): Promise<TokenScope[]> {
  // Common scopes based on the API examples
  // TODO: Replace with real API endpoint if available
  return [
    { id: 'topology:run', label: 'Topology Run', description: 'Run topologies' },
    { id: 'log:write', label: 'Log Write', description: 'Write logs' },
    { id: 'metric:write', label: 'Metric Write', description: 'Write metrics' },
    { id: 'worker:all', label: 'Worker All', description: 'All worker permissions' },
    { id: 'applications:all', label: 'Applications All', description: 'All application permissions' },
  ]
}
