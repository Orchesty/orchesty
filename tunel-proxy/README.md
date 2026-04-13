# Tunnel Proxy

High-performance bidirectional proxy enabling communication between the cloud-based Bridge and workers behind a firewall. Workers open a gRPC stream (tunnel) towards the proxy; the Bridge sends standard HTTP POST requests to the proxy which forwards them through the tunnel.

## Architecture

```
┌──────────────────── Cloud ────────────────────┐
│                                               │
│  Bridge ──HTTP POST──▶ Tunnel Proxy :8080     │
│                           │                   │
│                     gRPC bidi stream :50051   │
│                           │                   │
└───────────────────────────┼───────────────────┘
                            │ (outbound from worker)
┌──────────── Behind Firewall ──────────────────┐
│                           │                   │
│  Worker A ────────────────┘                   │
│  Worker B ────────────────┘                   │
│                                               │
└───────────────────────────────────────────────┘
```

### Request lifecycle

1. Worker opens `OpenTunnel` bidi stream and sends an identification `Frame` with its `worker_id`.
2. Bridge sends `POST /call/{worker_id}/{method}` to the proxy's HTTP server.
3. HTTP handler looks up the worker's gRPC stream, generates a UUID `request_id`, and sends a `Frame` through the stream.
4. Worker processes the request and sends a response `Frame` with the same `request_id`.
5. The proxy's `recvLoop` routes the response to the waiting HTTP handler via a channel.
6. HTTP handler writes the response payload and status code back to Bridge.

## Quick Start

### Run locally

```bash
go run ./cmd/server
```

### Docker

```bash
docker build -t tunel-proxy .
docker run -p 8080:8080 -p 50051:50051 tunel-proxy
```

### Docker Compose

```yaml
tunel-proxy:
  build: ./tunel-proxy
  ports:
    - "8080:8080"
    - "50051:50051"
  environment:
    GRPC_ADDR: ":50051"
    HTTP_ADDR: ":8080"
    REQUEST_TIMEOUT: "30"
```

## Configuration

| Variable | Description | Default |
|----------|-------------|---------|
| `GRPC_ADDR` | gRPC server listen address | `:50051` |
| `HTTP_ADDR` | HTTP server listen address | `:8080` |
| `REQUEST_TIMEOUT` | Max time to wait for a worker response (seconds) | `30` |

## API Reference

### HTTP — Bridge-facing

**`POST /call/{worker_id}/{path...}`**

- `{worker_id}` — identifier of the target worker (matches `Sdk.name`)
- `{path...}` — wildcard path forwarded as `Frame.method` (e.g. `connector/hubspot/action`)
- Request body: arbitrary payload forwarded to the worker
- Response: worker's payload with the worker's status code

| Status | Meaning |
|--------|---------|
| 200 | Worker responded successfully |
| 404 | Worker not connected |
| 502 | Failed to send frame to worker / worker disconnected |
| 504 | Worker did not respond within `REQUEST_TIMEOUT` |

### gRPC — Worker-facing

**`TunnelService.OpenTunnel(stream Frame) returns (stream Frame)`**

Bidirectional stream. The worker must send an identification frame as the first message:

```protobuf
Frame {
  worker_id: "my-worker-name"  // required, must match Sdk.name
}
```

Subsequent frames from the proxy are requests; the worker sends response frames with the same `request_id`:

```protobuf
// Request (proxy → worker)
Frame {
  worker_id:  "my-worker-name"
  request_id: "uuid-v4"
  method:     "connector/hubspot/action"
  payload:    <request body bytes>
}

// Response (worker → proxy)
Frame {
  worker_id:  "my-worker-name"
  request_id: "uuid-v4"           // must match request
  payload:    <response body bytes>
  status_code: 200
}
```

## Worker Setup

### 1. Add a tunnel worker in the platform UI

Create a new SDK with type **tunnel** (no host/URL needed). The UI generates a `.env` block that you copy into the worker's `.env` file.

**Cloud instances** generate a minimal block — the SDK derives all connection URLs from `TENANT_ID`:

```env
# --- Orchesty Tunnel Configuration ---
TUNNEL_ENABLED=true
TUNNEL_WORKER_ID=my-remote-worker

# --- Orchesty Platform Connection ---
ORCHESTY_API_KEY=abc123...
TENANT_ID=abc123-instance-id
```

**Self-hosted / dev instances** generate explicit URLs:

```env
# --- Orchesty Tunnel Configuration ---
TUNNEL_ENABLED=true
TUNNEL_WORKER_ID=my-remote-worker

# --- Orchesty Platform Connection ---
ORCHESTY_API_KEY=abc123...
BACKEND_URL=http://192.168.1.10:8080
STARTING_POINT_URL=http://192.168.1.10:82
WORKER_API_HOST=http://192.168.1.10:8081
```

In self-hosted mode, the worker also needs `TUNNEL_PROXY_URL` pointing to the proxy's gRPC endpoint (e.g. `192.168.1.10:50051`). Add it manually to the `.env` file.

### 2. SDK behavior

When `TUNNEL_ENABLED=true`, the SDK's `listen()` function starts a gRPC client instead of an HTTP server:

- Opens a bidirectional stream to `TUNNEL_PROXY_URL`
- Sends an identification frame with `TUNNEL_WORKER_ID`
- Receives request frames and routes them through the same internal handler as HTTP mode
- Sends response frames back through the stream
- Reconnects automatically on disconnect (configurable via `TUNNEL_RECONNECT_INTERVAL`, default `5000`ms)

### 3. Required npm dependencies for tunnel mode

```json
{
  "@grpc/grpc-js": "^1.12.x",
  "@grpc/proto-loader": "^0.7.x"
}
```

These can be added as `optionalDependencies` so workers without tunnel mode don't need gRPC.

## Platform Integration

### Bridge & topology-generator

**No code changes required.** The Bridge continues to send HTTP POST requests to `ActionUrl()`. For tunnel workers, the topology configuration sets:

- `Url` = `http://tunnel-proxy:8080`
- `ActionPath` = `call/{sdk_name}/connector/hubspot/action`

### Sdk document

New `type` field added to the `Sdk` MongoDB document:

```json
// HTTP worker (default, no change)
{ "name": "my-worker", "url": "node-sdk:8008", "type": "http" }

// Tunnel worker
{ "name": "my-remote-worker", "url": "", "type": "tunnel" }
```

### PHP backend changes

- **`TopologyManager.getSdkUrlMap()`** — returns `TUNNEL_PROXY_HOST` for tunnel SDKs instead of `Sdk.url`
- **`TopologyConfigFactory.getPaths()`** — prefixes action paths with `call/{sdk_name}/` for tunnel SDKs
- **`ServiceLocator.doRequest()`** — constructs URL via `http://{TUNNEL_PROXY_HOST}/call/{sdk_name}/{path}` for tunnel SDKs
- **`SdkRepository`** — tunnel workers sharing the proxy host are looked up by `name` instead of `host`
- **`SdkHandler.getEnv()`** — generates the `.env` block shown above. In cloud mode it emits `TENANT_ID`; in dev mode it emits explicit URLs from existing backend parameters.

The only tunnel-specific environment variable for the PHP backend is:

```
TUNNEL_PROXY_HOST=tunnel-proxy:8080
```

## Connection Lifecycle

1. **Registration** — Worker opens stream, sends identification frame. Proxy registers the worker in `ConnectionManager`.
2. **Keepalive** — gRPC server pings every 30s, expects pong within 10s. Idle connections close after 5 minutes.
3. **Reconnect** — On stream error/close, proxy unregisters the worker and closes all pending requests. The worker reconnects with the same `worker_id`; proxy replaces the old entry.
4. **Graceful shutdown** — On SIGTERM/SIGINT, the proxy stops accepting new connections, waits for in-flight requests, then closes all streams.

## Deployment

### Ports

| Port | Protocol | Purpose |
|------|----------|---------|
| 8080 | HTTP | Bridge requests |
| 50051 | gRPC | Worker tunnels |

### Docker

```bash
docker build -t tunel-proxy .
docker run -p 8080:8080 -p 50051:50051 tunel-proxy
```

The image is built from `distroless/static` — minimal attack surface, no shell.

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| `404 worker "X" not connected` | Worker hasn't opened a tunnel or disconnected | Check worker logs, verify `TUNNEL_PROXY_URL` and `TUNNEL_WORKER_ID` |
| `504 gateway timeout` | Worker didn't respond in time | Increase `REQUEST_TIMEOUT`, check worker processing time |
| `502 bad gateway` | Worker disconnected while processing | Worker crashed or network issue; will auto-reconnect |
| gRPC connection refused | Proxy not running or port blocked | Verify proxy is running, check firewall rules for port 50051 |
| Worker keeps reconnecting | Network instability | Check logs, adjust `TUNNEL_RECONNECT_INTERVAL` |

## Project Structure

```
tunel-proxy/
├── cmd/server/main.go           — entrypoint, wiring, graceful shutdown
├── internal/
│   ├── config/config.go         — environment configuration
│   └── tunnel/
│       ├── connection.go        — ConnectionManager (worker registry)
│       ├── grpc_server.go       — gRPC OpenTunnel implementation
│       ├── http_server.go       — HTTP handler for Bridge
│       └── pending.go           — PendingRequests (request/response channels)
├── proto/
│   ├── tunnel.proto             — protobuf definition
│   ├── tunnel.pb.go             — generated message code
│   └── tunnel_grpc.pb.go        — generated gRPC service code
├── Dockerfile                   — multi-stage build (distroless)
├── go.mod
└── go.sum
```
