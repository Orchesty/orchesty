const AUTH0_DOMAIN = import.meta.env.VITE_AUTH0_DOMAIN || ''
const AUTH0_CLIENT_ID = import.meta.env.VITE_AUTH0_CLIENT_ID || ''
const AUTH0_AUDIENCE = import.meta.env.VITE_AUTH0_AUDIENCE || ''
const AUTH0_DB_CONNECTION = 'Username-Password-Authentication'

interface RopgTokenResponse {
  access_token: string
  id_token: string
  scope: string
  expires_in: number
  token_type: string
}

interface Auth0ErrorResponse {
  error: string
  error_description: string
}

function isAuth0Error(data: unknown): data is Auth0ErrorResponse {
  return typeof data === 'object' && data !== null && 'error' in data
}

function decodeJwtPayload(token: string): Record<string, unknown> {
  const base64Url = token.split('.')[1]
  const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/')
  const jsonPayload = decodeURIComponent(
    atob(base64)
      .split('')
      .map((c) => '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2))
      .join(''),
  )
  return JSON.parse(jsonPayload)
}

export async function loginWithEmail(email: string, password: string): Promise<RopgTokenResponse> {
  const response = await fetch(`https://${AUTH0_DOMAIN}/oauth/token`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      grant_type: 'http://auth0.com/oauth/grant-type/password-realm',
      username: email,
      password,
      client_id: AUTH0_CLIENT_ID,
      audience: AUTH0_AUDIENCE,
      scope: 'openid profile email',
      realm: AUTH0_DB_CONNECTION,
    }),
  })

  const data = await response.json()

  if (!response.ok || isAuth0Error(data)) {
    throw new Error((data as Auth0ErrorResponse).error_description || 'Login failed')
  }

  return data as RopgTokenResponse
}

export async function requestPasswordReset(email: string): Promise<void> {
  const response = await fetch(`https://${AUTH0_DOMAIN}/dbconnections/change_password`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      client_id: AUTH0_CLIENT_ID,
      connection: AUTH0_DB_CONNECTION,
      email,
    }),
  })

  if (!response.ok) {
    const data = await response.json().catch(() => null)
    throw new Error(
      (data as Auth0ErrorResponse | null)?.error_description || 'Failed to send reset email',
    )
  }
}

export function injectTokensIntoAuth0Cache(tokens: RopgTokenResponse): void {
  const decoded = decodeJwtPayload(tokens.id_token)
  const expiresAt = Math.floor(Date.now() / 1000) + tokens.expires_in

  const cacheKey = `@@auth0spajs@@::${AUTH0_CLIENT_ID}::${AUTH0_AUDIENCE}::openid profile email`
  const cacheValue = {
    body: {
      access_token: tokens.access_token,
      id_token: tokens.id_token,
      scope: tokens.scope || 'openid profile email',
      expires_in: tokens.expires_in,
      token_type: tokens.token_type,
      decodedToken: {
        claims: decoded,
        user: decoded,
      },
    },
    expiresAt,
  }

  localStorage.setItem(cacheKey, JSON.stringify(cacheValue))

  const userKey = `@@auth0spajs@@::${AUTH0_CLIENT_ID}::@@user@@`
  localStorage.setItem(
    userKey,
    JSON.stringify({ decodedToken: { claims: decoded, user: decoded } }),
  )
}
