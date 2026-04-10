# Cloud Controller

Cloud Controller provisions and removes Orchesty SaaS instances in Kubernetes.

It orchestrates these systems:
- MongoDB (database user and databases)
- RabbitMQ (vhost, user, permissions)
- Kubernetes (namespace and secrets)
- Helm (Orchesty chart install)
- Kong Gateway (ingress service and route registration)
- GCS / Object Storage (log bucket and HMAC credential management)

## Features

- Create new instance via HTTP API
- Update existing instance via HTTP API
- Delete existing instance via HTTP API
- Health endpoint checking MongoDB, RabbitMQ, Kubernetes, Kong, and GCS
- Rollback on partial create failure (including Kong routes and GCS buckets)
- Input validation for create requests
- Conditional Kong Gateway ingress registration (toggleable via config)
- Conditional GCS bucket and HMAC key management for Loki log storage (toggleable via config)
- Applinth EC key pair generation (secp521r1 / P-521)
- Grafana admin password generation
- Unit and integration-like tests for all main packages

## Project Structure

- `main.go` - app bootstrap, dependency wiring, graceful shutdown
- `pkg/config` - environment-based configuration and logger setup
- `pkg/server` - HTTP handlers and response mapping
- `pkg/service` - create/update/delete orchestration logic and rollback
- `pkg/models` - DTOs, generated credentials (including EC keys), API response models
- `pkg/mongodb` - MongoDB client actions (create/delete users, ping)
- `pkg/rabbitmq` - RabbitMQ HTTP API client
- `pkg/kubernetes` - Kubernetes namespace/secrets and Helm integration
- `pkg/kubernetes/templates` - Helm chart and values YAML templates
- `pkg/ingressGW` - Kong Gateway Admin API client (services and routes)
- `pkg/objectStorage` - GCS JSON API client (buckets and HMAC keys)

## HTTP API

Base URL: `http://localhost:8080`

### GET /status

Checks availability of MongoDB, RabbitMQ, and Kubernetes.

Success response example:

```json
{
  "status": "ok",
  "checks": {
    "rabbitmq": "ok",
    "mongodb": "ok",
    "k8s": "ok",
    "kong": "ok",
    "gcs": "ok"
  }
}
```

`kong` and `gcs` checks appear only when `KONG_ENABLED` / `GCS_ENABLED` is `true`.

Failure response example:

```json
{
  "status": "error",
  "checks": {
    "rabbitmq": "error",
    "mongodb": "ok",
    "k8s": "timeout",
    "kong": "ok",
    "gcs": "ok"
  },
  "error": "rabbitmq: ...; k8s: health check timed out"
}
```

### POST /instance

Creates a new instance.

Request body:

```json
{
  "instanceDisplayName": "My Instance",
  "instanceUrlPrefix": "my-instance",
  "userName": "admin@example.com",
  "customizations": {
    "workers": [
      {
        "name": "default",
        "image": "hanaboso/demo-worker:latest",
        "sdkType": "nodejs",
        "envs": [
          {
            "key": "DEBUG",
            "value": "true"
          }
        ]
      }
    ],
    "valkey": {
      "enabled": true,
      "persistentStorage": {
        "enabled": true,
        "size": 4
      },
      "limit": {
        "cpu": 500,
        "memory": 1,
        "storage": 2
      }
    },
    "logs": {
      "enabled": true,
      "grafanaEnabled": true,
      "retentionPeriod": 720,
      "logsStorageSize": 10
    },
    "applinth": {
      "enabled": true
    },
    "resourceLimits": {
      "enabled": true,
      "cpu": "500m",
      "memory": "512Mi"
    },
    "traceAuditing": false,
    "enterpriseDashboards": false,
    "auditLogs": false,
    "useBundle": false
  }
}
```

Notes:
- `instanceDisplayName` is required.
- `instanceUrlPrefix` is required.
- `userName` is optional (default: `orchesty@hanaboso.com`).
- `customizations.valkey.limit` is optional; when set, configures CPU (millicores), memory (Gi), and ephemeral-storage (Gi) limits.
- `customizations.logs` is optional; controls Grafana/Loki/Alloy deployment. When `enabled`, a GCS bucket and HMAC credentials are provisioned (if `GCS_ENABLED`).
- `customizations.logs.grafanaEnabled` generates a random Grafana admin password stored in the K8s secret.
- `customizations.logs.retentionPeriod` is optional; log retention period in hours.
- `customizations.logs.logsStorageSize` is optional; log storage size in Gi.
- `customizations.applinth` is optional; when `enabled`, generates an EC key pair (secp521r1) for JWE and stores PEM-encoded keys in the K8s secret.
- `customizations.resourceLimits` is optional; when `enabled`, sets CPU and memory limits on Orchesty pods.
- `customizations.traceAuditing` is optional; enables trace auditing.
- `customizations.enterpriseDashboards` is optional; enables enterprise dashboards.
- `customizations.auditLogs` is optional; enables audit logs.
- `customizations.useBundle` is optional; switches to bundled deployment mode.

Success response (`201`):

```json
{
  "instance": "abc123def4",
  "instanceDisplayName": "My Instance",
  "instanceUrlPrefix": "my-instance",
  "userName": "admin@example.com",
  "userPassword": "generated-password",
  "grafanaPassword": "generated-password",
  "applinthPublicKey": "-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----\n"
}
```

Error mapping:
- `400` invalid input
- `409` instance namespace already exists
- `500` internal error

### PATCH /instance

Updates an existing instance.

Request body:

```json
{
  "instance": "instance-abc123def4",
  "instanceDisplayName": "Updated Name",
  "customizations": {
    "workers": [
      {
        "name": "default",
        "image": "hanaboso/demo-worker:v2.0.0",
        "sdkType": "nodejs",
        "envs": []
      }
    ],
    "valkey": {
      "enabled": true,
      "persistentStorage": {
        "enabled": true,
        "size": 8
      }
    }
  }
}
```

Notes:
- `instance` is required.
- `instanceDisplayName` and `customizations` are optional.
- Only provided fields are updated.

Success response (`200`):

```json
{
  "instance": "abc123def4",
  "instanceDisplayName": "Updated Name",
  "instanceUrlPrefix": "my-instance"
}
```

Error mapping:
- `400` invalid input or missing required fields
- `500` internal error

### DELETE /instance?instance=<name>

Deletes instance resources.

Success: `204 No Content`

Error mapping:
- `400` when `instance` query parameter is missing/empty
- `500` internal error (aggregated errors from multiple systems)

## Configuration

Configuration is loaded from environment variables in `pkg/config/config.go`.

### Required

- `MONGODB_DSN`
- `RABBIT_URL`

### Optional

- `APP_DEBUG` (default: `false`)
- `APP_PORT` (default: `8080`)
- `RABBIT_ADMIN_USER` (default: `guest`)
- `RABBIT_ADMIN_PASS` (default: `guest`)
- `K8S_CLUSTER_CONFIG` (default: empty, then in-cluster config is used)
- `HELM_ROOT_DIR_FOR_FILES` (default: `/tmp/helm`)
- `HELM_ORCHESTY_VERSION` (default: `~2.1.15`)
- `HELM_BRIDGEPOOL_KEY` (default: `bridgepool`)

### Kong Gateway

- `KONG_ENABLED` (default: `false`) - enable Kong ingress registration
- `KONG_ADMIN_URL` (default: `http://kong:8001`) - Kong Admin API URL
- `KONG_DOMAIN_SUFFIX` (default: `eu1.cloud.orchesty.io`) - domain suffix for generated routes

### GCS / Object Storage

- `GCS_ENABLED` (default: `false`) - enable GCS bucket management
- `GCS_PROJECT_ID` - GCP project ID
- `GCS_CREDENTIALS_FILE` - path to service account JSON key
- `GCS_LOCATION` (default: `eu`) - bucket location
- `GCS_ENDPOINT` - custom GCS API endpoint (for testing with fake-gcs-server)
- `GCS_SERVICE_ACCOUNT_EMAIL` - service account email for HMAC key creation

You can bootstrap local env file from `.env.dist`:

```bash
make .env
```

## Local Development

The module uses Docker Compose for local development and test commands.

1. Create env file:

```bash
make .env
```

2. Start containers:

```bash
make docker-up-force
```

3. Enter app container if needed:

```bash
docker-compose exec app sh
```

4. Stop and clean:

```bash
make docker-down-clean
```

## Testing and Linting

Project standard test command:

```bash
make fasttest
```

`make fasttest` runs:
- `go fmt ./...`
- `revive` lint (tests excluded from revive checks)
- `go test` with coverage profile
- HTML coverage report generation

Coverage outputs:
- `var/coverage.out`
- `var/coverage.html`

## Build

Container image build (uses multi-stage Dockerfile):

```bash
make build
```

## Runtime Notes

- Service listens on `APP_PORT`.
- Graceful shutdown timeout is 10 seconds.
- Health check timeout for each dependency is 5 seconds.
- Kubernetes API request timeout is 20 seconds.

## Quick cURL Examples

Health:

```bash
curl -s http://localhost:8080/status | jq
```

Create instance:

```bash
curl -s -X POST http://localhost:8080/instance \
  -H "Content-Type: application/json" \
  -d '{
    "instanceDisplayName": "Demo Instance",
    "instanceUrlPrefix": "instance",
    "userName": "admin@example.com",
    "customizations": {
      "workers": [
        {
          "name": "default",
          "image": "hanaboso/demo-worker:latest",
          "sdkType": "nodejs"
        }
      ]
    }
  }' | jq
```

Delete instance:

```bash
curl -i -X DELETE "http://localhost:8080/instance?instance=instance-abc123def4"
```

## Kubernetes Secret Keys

The `orchesty-secrets` secret created per instance contains:

| Key | Source | Condition |
|-----|--------|-----------|
| `backend_jwt_key` | generated | always |
| `crypt_secret` | generated | always |
| `orchesty_api_key` | generated | always |
| `mongodb_dsn` | generated | always |
| `mongodb_db` | instance name | always |
| `metrics_dsn` | generated | always |
| `metrics_db` | instance name | always |
| `rabbitmq_dsn` | generated | always |
| `rabbitmq_url` | config | always |
| `rabbitmq_user` | instance name | always |
| `rabbitmq_password` | generated | always |
| `oc_instance_display_name` | request | always |
| `oc_instance_url_prefix` | request | always |
| `oc_user_name` | request | always |
| `oc_user_password` | generated | always |
| `applinth_jwe_private_key` | generated EC P-521 PEM | `applinth.enabled` |
| `applinth_jwe_public_key` | generated EC P-521 PEM | `applinth.enabled` |
| `admin-user` | `"admin"` | `logs.grafanaEnabled` |
| `admin-password` | generated | `logs.grafanaEnabled` |
| `s3-endpoint` | config (`GCS_ENDPOINT` host or `storage.googleapis.com`) | `logs.enabled` |
| `s3-bucket` | `logs-{instance}` | `logs.enabled` |
| `s3-access-key` | GCS HMAC | `logs.enabled` |
| `s3-secret-key` | GCS HMAC | `logs.enabled` |
