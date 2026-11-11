import { inject } from 'vue'

export interface AuthorizationProvider {
  can: (permission: string) => boolean
  hasRole: (role: string) => boolean
}

const AUTHORIZATION_KEY = Symbol('authorization')

const defaultProvider: AuthorizationProvider = {
  can: () => true,
  hasRole: () => true,
}

export function provideAuthorization(provider: AuthorizationProvider) {
  return { key: AUTHORIZATION_KEY, value: provider }
}

export function useAuthorization(): AuthorizationProvider {
  return inject<AuthorizationProvider>(AUTHORIZATION_KEY, defaultProvider)
}

export { AUTHORIZATION_KEY }
