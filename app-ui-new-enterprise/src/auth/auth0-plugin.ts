import { createAuth0 } from '@auth0/auth0-vue'
import { AUTH0_DOMAIN, AUTH0_CLIENT_ID, AUTH0_AUDIENCE } from '@/config'

const domain = AUTH0_DOMAIN
const clientId = AUTH0_CLIENT_ID
const audience = AUTH0_AUDIENCE

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
