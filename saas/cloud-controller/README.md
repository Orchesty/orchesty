# Cloud Controller

Cloud Controller provisions and removes Orchesty SaaS instances in Kubernetes.

It orchestrates these systems:
- MongoDB (database user and databases)
- RabbitMQ (vhost, user, permissions)
- Kubernetes (namespace and secrets)
- Helm (Orchesty chart install)

## Features

- Create new instance via HTTP API
- Delete existing instance via HTTP API
- Health endpoint checking MongoDB, RabbitMQ, and Kubernetes
- Rollback on partial create failure
- Input validation for create requests
- Unit and integration-like tests for all main packages

## Project Structure

- `main.go` - app bootstrap, dependency wiring, graceful shutdown
- `pkg/config` - environment-based configuration and logger setup
- `pkg/server` - HTTP handlers and response mapping
- `pkg/service` - create/delete orchestration logic and rollback
- `pkg/models` - DTOs, generated credentials, API response models
- `pkg/mongodb` - MongoDB client actions (create/delete users, ping)
- `pkg/rabbitmq` - RabbitMQ HTTP API client
- `pkg/kubernetes` - Kubernetes namespace/secrets and Helm integration
- `pkg/kubernetes/templates` - Helm chart and values YAML templates

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
    "k8s": "ok"
  }
}
```

Failure response example:

```json
{
  "status": "error",
  "checks": {
    "rabbitmq": "error",
    "mongodb": "ok",
    "k8s": "timeout"
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
      "grafanaEnabled": true
    }
  }
}
```

Notes:
- `instanceDisplayName` is required.
- `instanceUrlPrefix` is required.
- `userName` is optional (default: `orchesty@hanaboso.com`).
- `customizations.valkey.limit` is optional; when set, configures CPU (millicores), memory (Gi), and ephemeral-storage (Gi) limits.
- `customizations.logs` is optional; controls Grafana/Loki/Alloy deployment.

Success response (`201`):

```json
{
  "instance": "abc123def4",
  "instanceDisplayName": "My Instance",
  "instanceUrlPrefix": "my-instance",
  "userName": "admin@example.com",
  "userPassword": "generated-password"
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

## Known Limitations / TODO

- `applinth_jwe_private_key` generation is not implemented yet (marked TODO in Kubernetes secret creation).

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
      "workerImage": "hanaboso/demo-worker:latest",
      "workerSdkType": "nodejs"
    }
  }' | jq
```

Delete instance:

```bash
curl -i -X DELETE "http://localhost:8080/instance?instance=instance-abc123def4"
```
