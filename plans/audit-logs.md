# Audit Logs — Implementation Plan

## Overview

Add audit logging to the enterprise application. Every mutating API action (create, update, delete, run, publish) will be automatically captured by a Symfony `kernel.terminate` subscriber. The frontend already has a complete UI with mock data — this plan replaces mocks with real API calls.

## Architecture

```
Request → AclSubscriber (authz) → Controller → Response
                                                    ↓
                                          AuditLogSubscriber (kernel.terminate)
                                                    ↓
                                              AuditLog (MongoDB)
                                                    ↑
                                          AuditLogController (GET /api/audit-logs)
                                                    ↑
                                              Frontend UI
```

The subscriber runs **after** the response is sent to the client (`kernel.terminate`), so audit logging adds zero latency to API calls.

## File Structure

```
pf-bundles-enterprise/src/
├── AuditLog/
│   ├── Document/
│   │   └── AuditLog.php              # MongoDB document
│   ├── Enum/
│   │   └── AuditActionEnum.php       # created, updated, deleted, executed, published
│   ├── Repository/
│   │   └── AuditLogRepository.php    # Filtered queries, pagination
│   └── Subscriber/
│       └── AuditLogSubscriber.php    # kernel.terminate — captures mutating requests
├── HbPFEnterpriseConfiguratorBundle/
│   └── Handler/
│       └── AuditLogHandler.php       # Query logic for the API endpoint
└── HbPFEnterpriseApiGatewayBundle/
    └── Controller/
        └── AuditLogController.php    # GET /api/audit-logs, GET /api/audit-logs/{id}
```

## Tasks

### 1. Backend: AuditLog document

Create `AuditLog` MongoDB document in `src/AuditLog/Document/AuditLog.php`:

| Field | Type | Description |
|---|---|---|
| `id` | string | Auto-generated MongoDB ID |
| `timestamp` | date | When the action occurred |
| `userId` | string | ID of the user who performed the action |
| `userEmail` | string | Email of the user (denormalized for display) |
| `action` | string | One of: `created`, `updated`, `deleted`, `executed`, `published`, `exported` |
| `resource` | string | Resource type from `ResourceEnum` (e.g. `topology`, `application`, `user`) |
| `resourceId` | string | ID of the affected resource (extracted from URL path) |
| `resourceName` | string\|null | Human-readable name if available (e.g. topology name) |
| `method` | string | HTTP method (POST, PUT, PATCH, DELETE) |
| `path` | string | API path (e.g. `/api/topologies/abc123`) |
| `ip` | string | Client IP address |
| `statusCode` | int | HTTP response status code |

Indexes:
- `timestamp` DESC (primary query order)
- `userId` ASC
- `resource` ASC
- TTL index on `timestamp` (e.g. 365 days) for automatic cleanup

Create `AuditActionEnum.php` with constants: `CREATED`, `UPDATED`, `DELETED`, `EXECUTED`, `PUBLISHED`.

### 2. Backend: AuditLogRepository

Create `AuditLogRepository.php` with a paginated query method supporting:
- Text search (user email, resource name)
- Filter by action type
- Filter by resource type
- Filter by time range (from/to dates)
- Sorting (default: timestamp DESC)
- Pagination (page, limit)

### 3. Backend: AuditLogSubscriber

Create `AuditLogSubscriber.php` subscribing to `KernelEvents::TERMINATE`.

Logic:
1. Skip non-main requests
2. Skip GET, HEAD, OPTIONS methods (read-only)
3. Skip if response status is not 2xx (failed actions shouldn't be logged)
4. Get the authenticated user from Security
5. Resolve the audit action from HTTP method:
   - POST → `created` (or `executed` for topology run, `published` for topology publish)
   - PUT/PATCH → `updated`
   - DELETE → `deleted`
6. Resolve the resource type — reuse `ACL_PREFIX_MAP` pattern from `AclSubscriber` (longest prefix match on the path)
7. Extract resource ID from the URL path (regex: last path segment matching `[a-f0-9]{24}`)
8. Optionally resolve resource name (for topologies: load from DB; for others: from request body if available)
9. Persist the `AuditLog` document

Special cases to detect from path patterns:
- `/api/topologies/{id}/run` → action = `executed`
- `/api/topologies/{id}/publish` → action = `published`
- `/api/user/invite` → action = `created`, resource = `user`
- `/api/applications/{key}/changeState` → action = `updated`

Paths to skip (not meaningful audit events):
- `/api/user/check_logged`, `/api/user/whoami`, `/api/user/me/groups`
- `/api/audit-logs` (don't audit the audit endpoint itself)

### 4. Backend: AuditLogHandler

Create `AuditLogHandler.php` in `HbPFEnterpriseConfiguratorBundle/Handler/`:
- `getAuditLogs(array $filters): array` — delegates to repository, returns paginated response
- `getAuditLog(string $id): array` — single entry detail

### 5. Backend: AuditLogController + API Gateway

Create `AuditLogController.php` in `HbPFEnterpriseApiGatewayBundle/Controller/`:
- `GET /api/audit-logs` — paginated list with query params: `filter` (JSON with search, action, resource, timeRange), `page`, `limit`, `sort`, `order`
- `GET /api/audit-logs/{id}` — single entry detail

Response format (matching existing frontend types):
```json
{
  "items": [
    {
      "id": "...",
      "timestamp": "2026-03-26T14:30:00Z",
      "user": "admin@example.com",
      "userId": "abc123",
      "object": "Topology: My Integration",
      "objectId": "def456",
      "action": "Updated",
      "note": "PUT /api/topologies/def456"
    }
  ],
  "total": 42,
  "page": 1,
  "limit": 20
}
```

The `object` field = `"{Resource}: {resourceName}"` (e.g. "Topology: My Integration") or just the resource type if name is not available.
The `note` field = `"{METHOD} {path}"` — the raw HTTP call for technical context.

### 6. Backend: Service registration

Register services in existing config files:

**`HbPFEnterpriseApiGatewayBundle/Resources/config/services.yml`** — add `AuditLogSubscriber` with `kernel.event_subscriber` tag.

**`HbPFEnterpriseApiGatewayBundle/Resources/config/controllers.yml`** — add `AuditLogController`.

### 7. Backend: ACL for audit-logs endpoint

Add to `AclSubscriber::ACL_PREFIX_MAP`:
```php
'/api/audit-logs' => ['DEFAULT' => [ActionEnum::READ, ResourceEnum::SETTINGS]],
```

Only users with `settings:read` (System Manager+) can view audit logs.

### 8. Frontend: Replace mock service with real API

Update `app-ui-new-enterprise/src/services/auditLogsService.ts`:
- Replace mock `fetchAuditLogs` with real `api.get('/api/audit-logs', { params: ... })`
- Replace mock `fetchAuditLogDetail` with real `api.get('/api/audit-logs/{id}')`
- Update `exportAuditLogs` to call real paginated API (fetch all pages, generate CSV client-side)
- Remove mock data import

### 9. Frontend: Adjust types if needed

Verify `app-ui-new-enterprise/src/types/audit-logs.ts` matches the API response. Current type:
```typescript
interface AuditLogEntry {
  id: string
  timestamp: string
  user: string       // email
  userId: string
  object: string     // "Topology: Name"
  objectId: string
  action: AuditAction // 'Created' | 'Updated' | 'Deleted' | ...
  note: string       // "PUT /api/topologies/abc123"
}
```

Backend response should match this format. Capitalize action values in the API response to match the frontend type.

### 10. Frontend: Add permission to audit-logs route

Add `permission: 'settings:read'` to the audit-logs route meta in `app-ui-new-enterprise/src/router/index.ts`, so the page is hidden and access-denied for users without settings access.
