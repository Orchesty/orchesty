# Orchesty Enterprise UI

Enterprise frontend for Orchesty. Consumes `app-ui-new` (core) as a library and adds enterprise-specific features (ACL, SSO, cloud integration).

## Authentication Modes

The application supports three authentication modes, controlled by environment variables. The frontend variables are `VITE_*` prefixed (Vite convention), the backend variables are plain.

### 1. Cloud mode

Automatic redirect to Auth0 — no login form is shown, just a spinner. Used for cloud-hosted deployments where user management is handled externally.

**Frontend (`VITE_*`):**

| Variable | Example |
|---|---|
| `VITE_AUTH0_DOMAIN` | `dev-xxx.eu.auth0.com` |
| `VITE_AUTH0_CLIENT_ID` | `1XIsyOq2kpTDt4qOlop2rEAQzPcE2Q5p` |
| `VITE_AUTH0_AUDIENCE` | `https://api.orchesty.cloud` |
| `VITE_AUTH0_REDIRECT` | `true` |

**Backend:**

| Variable | Example |
|---|---|
| `AUTH0_DOMAIN` | `dev-xxx.eu.auth0.com` |
| `AUTH0_AUDIENCE` | `https://api.orchesty.cloud` |

### 2. On-prem SSO mode

Login page shows Auth0 social buttons (Google, GitHub) alongside a classic email/password form. Used for on-prem deployments with internet access where SSO is desired but manual login is still an option.

**Frontend (`VITE_*`):**

| Variable | Example |
|---|---|
| `VITE_AUTH0_DOMAIN` | `dev-xxx.eu.auth0.com` |
| `VITE_AUTH0_CLIENT_ID` | `1XIsyOq2kpTDt4qOlop2rEAQzPcE2Q5p` |
| `VITE_AUTH0_AUDIENCE` | `https://api.orchesty.cloud` |
| `VITE_AUTH0_REDIRECT` | _(not set or not `true`)_ |

**Backend:**

| Variable | Example |
|---|---|
| `AUTH0_DOMAIN` | `dev-xxx.eu.auth0.com` |
| `AUTH0_AUDIENCE` | `https://api.orchesty.cloud` |

### 3. Legacy mode (email/password only)

Classic email/password form only. No Auth0, no SSO. Used for fully offline / air-gapped on-prem deployments.

**Frontend:** None of the `VITE_AUTH0_*` variables are set.

**Backend:** `AUTH0_DOMAIN` and `AUTH0_AUDIENCE` are not set (default to empty string).

### How it works

The mode is determined at build/runtime by `src/auth/auth0-plugin.ts`:

```typescript
isAuth0Enabled  = !!(VITE_AUTH0_DOMAIN && VITE_AUTH0_CLIENT_ID)
isAuth0Redirect = isAuth0Enabled && VITE_AUTH0_REDIRECT === 'true'
```

- **Cloud:** `isAuth0Enabled = true`, `isAuth0Redirect = true` — auto-redirect, no form
- **On-prem SSO:** `isAuth0Enabled = true`, `isAuth0Redirect = false` — social buttons + form
- **Legacy:** `isAuth0Enabled = false` — email/password form only

### Where to set the variables

- **Docker Compose (dev):** `clients/demo/docker-compose.yml` — `environment` section of `frontend-enterprise` (frontend) and `backend-enterprise` (backend) services.
- **Production:** Inject as environment variables into the respective containers. Frontend vars must be available at Vite build time (or use runtime env injection in the Docker entrypoint).

### Backend dual-mode authenticator

The backend uses a single `Auth0Authenticator` that handles both modes internally:

- When `AUTH0_DOMAIN` is set and the incoming JWT has RS256 algorithm (Auth0 token) — verifies via Auth0 JWKS.
- Otherwise — delegates to the legacy `JWTAuthenticator` (HS512).

This is configured in `pf-bundles-enterprise/config/packages/security.yaml` and `pf-bundles-enterprise/config/services.yaml`.

## Project Setup

```sh
npm install
```

### Compile and Hot-Reload for Development

```sh
npm run dev
```

### Type-Check, Compile and Minify for Production

```sh
npm run build
```

### Lint with [ESLint](https://eslint.org/)

```sh
npm run lint
```
