import { createAuth0 } from '@auth0/auth0-vue'

const domain = import.meta.env.VITE_AUTH0_DOMAIN || ''
const clientId = import.meta.env.VITE_AUTH0_CLIENT_ID || ''
const audience = import.meta.env.VITE_AUTH0_AUDIENCE || ''

export const isAuth0Enabled = !!(domain && clientId)

export const auth0Plugin = isAuth0Enabled
  ? createAuth0({
      domain,
      clientId,
      authorizationParams: {
        redirect_uri: window.location.origin,
        ...(audience && { audience }),
      },
      cacheLocation: 'localstorage',
    })
  : null
