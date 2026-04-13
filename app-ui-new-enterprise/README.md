# Orchesty Enterprise UI

Enterprise frontend for Orchesty. Consumes `app-ui-new` (core) as a library and adds enterprise-specific features (ACL, SSO, cloud integration).

## Environment Variables

### Frontend (`VITE_*`)

| Variable | Required | Description |
|---|---|---|
| `VITE_BACKEND_URL` | Yes | Backend API base URL (e.g. `http://127.0.0.1:8085`) |
| `VITE_AUTH0_DOMAIN` | SSO/Cloud | Auth0 tenant domain (e.g. `dev-xxx.eu.auth0.com`) |
| `VITE_AUTH0_CLIENT_ID` | SSO/Cloud | Auth0 SPA application Client ID |
| `VITE_AUTH0_AUDIENCE` | SSO/Cloud | Auth0 API audience identifier (e.g. `https://api.orchesty.cloud`) |

### Backend

| Variable | Required | Description |
|---|---|---|
| `BACKEND_URL` | Yes | Public URL of this backend instance |
| `FRONTEND_URL` | Yes | Public URL of the frontend (used in emails and redirects) |
| `AUTH0_DOMAIN` | SSO/Cloud | Auth0 tenant domain, must match the frontend value |
| `AUTH0_AUDIENCE` | SSO/Cloud | Auth0 API audience, must match the frontend value |
| `JWT_KEY` | Yes | Secret key for legacy HS512 JWT authentication |
| `JWE_PRIVATE_KEY` | Yes | EC private key (PEM) for JWE token encryption |
| `EMAIL_DSN` | Yes | SMTP transport DSN for sending emails (e.g. `smtp://mailhog:1025`) |
| `ORCHESTY_CLOUD_URL` | Cloud | Cloud backend API URL (e.g. `http://host.docker.internal:3000`). Enables cloud mode when set. |
| `ORCHESTY_CLOUD_FRONTEND_URL` | Cloud | Cloud frontend URL (e.g. `http://localhost:5173`). Used for email links in cloud mode. |
| `ORCHESTY_CLOUD_INSTANCE_ID` | Cloud | Instance ID registered in the cloud platform |
| `ORCHESTY_CLOUD_INSTANCE_SECRET` | Cloud | Shared secret for cloud API authentication |
| `ORCHESTY_CLOUD_INSTANCE_NAME` | Cloud | Human-readable instance name displayed in the browser title (e.g. `My Company`). Sets the page title to `[name] - Orchesty` in cloud mode. |
| `SYSTEM_ORCHESTY_URL` | Emails | Orchesty starting-point URL for triggering system topologies (transactional emails). If empty, uses the local starting-point. |

### Feature Flags

Server-driven feature flags control which enterprise features are available in the UI based on the user's subscription. They are set as environment variables on the backend and served to the frontend via `GET /api/status` in the `features` object. In on-prem mode (non-cloud), all features are always enabled.

| Variable | Default | Controls |
|---|---|---|
| `ORCHESTY_FEATURE_ENTERPRISE_DASHBOARDS` | `false` | Enterprise Control Center dashboard (multi-tab with Overview, Applications, Connectors, Topologies, Processes, Limiter). When `false`, only the core Processes grid is shown. |
| `ORCHESTY_FEATURE_TRACE_AUDITING` | `false` | Trace view + sidebar link, Trace drawer icon in topbar, and "Audit entities" tab in Settings. |
| `ORCHESTY_FEATURE_AUDIT_LOGS` | `false` | Audit logs page + navbar menu item. |
| `ORCHESTY_FEATURE_PULSE` | `false` | "Context" tab in topology detail view. |

## Authentication Modes

The application supports three authentication modes, determined by environment variables.

### 1. Cloud mode

Automatic redirect to Auth0 — no login form is shown. User management and sign-in/out are handled by the cloud platform.

Cloud mode is detected dynamically: the frontend calls `GET /api/status` on the backend, which returns `cloudMode: true` when `ORCHESTY_CLOUD_URL` is configured. The composable `useCloudMode()` exposes this flag to the router and components.

**Required variables:** All `VITE_AUTH0_*` frontend vars + all `ORCHESTY_CLOUD_*` backend vars.

### 2. On-prem SSO mode

Login page shows Auth0 social buttons (Google, GitHub) alongside a classic email/password form. Used for on-prem deployments with internet access where SSO is desired.

**Required variables:** All `VITE_AUTH0_*` frontend vars + `AUTH0_DOMAIN` and `AUTH0_AUDIENCE` on the backend. `ORCHESTY_CLOUD_*` variables are **not set**.

### 3. Legacy mode (email/password only)

Classic email/password form only. No Auth0, no SSO. Used for fully offline / air-gapped deployments.

**Required variables:** None of the `VITE_AUTH0_*` frontend vars. `AUTH0_DOMAIN` and `AUTH0_AUDIENCE` are not set on the backend.

### How it works

Auth0 availability is determined in `src/auth/auth0-plugin.ts`:

```typescript
isAuth0Enabled = !!(VITE_AUTH0_DOMAIN && VITE_AUTH0_CLIENT_ID)
```

Cloud mode and feature flags are determined at runtime from the backend response:

```typescript
// src/composables/useCloudMode.ts
const res = await fetch(`${BACKEND_URL}/api/status`)
const data = await res.json()
cloudMode.value = data.cloudMode === true
// data.features = { enterpriseDashboards, traceAuditing, auditLogs, pulse }
```

- **Cloud:** `isAuth0Enabled = true`, backend reports `cloudMode = true` — auto-redirect to Auth0, no form
- **On-prem SSO:** `isAuth0Enabled = true`, `cloudMode = false` — social buttons + email/password form
- **Legacy:** `isAuth0Enabled = false` — email/password form only

### Backend dual-mode authenticator

The backend uses a single `Auth0Authenticator` that handles both JWT types:

- When `AUTH0_DOMAIN` is set and the incoming JWT has RS256 algorithm (Auth0 token) — verifies via Auth0 JWKS.
- Otherwise — delegates to the legacy `JWTAuthenticator` (HS512, signed with `JWT_KEY`).

Configured in `pf-bundles-enterprise/config/packages/security.yaml` and `pf-bundles-enterprise/config/services.yaml`.

## Where to set the variables

- **Docker Compose (dev):** `clients/demo/docker-compose.yml` — `environment` section of `frontend-new-enterprise` (frontend) and `backend-enterprise` (backend) services.
- **Production:** Inject as environment variables into the respective containers. Frontend vars must be available at Vite build time (or use runtime env injection in the Docker entrypoint).

## Roles & Permissions

The enterprise application uses a hierarchical role-based access control system. Each user is assigned exactly one **role** (an immutable preset). Additionally, users can be added to **access groups** for fine-grained, per-topology permissions (e.g. `run`).

### Roles (from lowest to highest)

| Role | Level | Description |
|---|---|---|
| Chat User | 5 | Access to Trace chat only. No sidebar. Topology run permissions via access groups. |
| Monitoring | 4 | Read-only access to dashboard, topologies, scheduled tasks, processes, logs, failed messages. |
| Process Management | 3 | Monitoring + manage scheduled tasks, enable/disable topologies, start events, reprocess failed messages. |
| Developer | 2 | Process Management + full topology editing/deletion, application management. |
| System Manager | 1 | Developer + settings, SDKs, API tokens. Access to system topologies and sys-worker. |
| Super Admin | 0 | Full access including user and group management. |

Each higher role inherits all permissions from the levels below it.

### Access Groups

Access groups are user-defined and stored in the database. They are used to assign per-topology permissions (`read`, `edit`, `delete`, `run`) to specific users. This is especially useful for the Chat User role, which can run specific topologies via MCP without seeing them in the UI.

### System Topologies

Topologies placed in a category with `system: true` are protected:

- **Delete** is always blocked (even for Super Admin)
- **Write/Edit** requires System Manager or higher
- The system folder is **hidden from the sidebar** for users below System Manager

To mark a category as system, set the `system` field in MongoDB:

```javascript
db.Category.updateOne({ name: "system" }, { $set: { system: true } })
```

### System Workers

Workers listed in the `system_worker_names` parameter (`pf-bundles-enterprise/config/services.yaml`) are hidden from the Applications page for users below System Manager. The parameter is exposed via `GET /api/status` as `systemWorkerNames` and injected into the frontend via `SYSTEM_WORKERS_KEY` provide/inject.

### UI Visibility Rules

- Sidebar items and pages are hidden based on the user's role permissions
- Direct navigation to a restricted page shows an "Access denied" screen
- The Chat User role sees no sidebar and is redirected to `/trace` on login
- The Trace drawer button (topbar) is hidden for Chat User (they use the full Trace page instead)

## Project Setup

```sh
pnpm install
```

### Compile and Hot-Reload for Development

```sh
pnpm run dev
```

### Type-Check, Compile and Minify for Production

```sh
pnpm run build
```

### Lint with [ESLint](https://eslint.org/)

```sh
pnpm run lint
```
