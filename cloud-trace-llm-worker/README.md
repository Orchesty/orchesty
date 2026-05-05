# Cloud Trace LLM Worker

Dedicated Orchesty worker hosting the **default LLM applications** used by Trace cloud-relay. Registered only with the Orchesty Cloud system instance — never deployed inside a user instance.

This worker is the destination of the cloud-relay forward path:

```
Trace bridge (user instance)
  -> User instance PHP backend (gate + quota)
  -> Cloud backend Node.js proxy (auth + defensive rate limit)
  -> System instance API gateway
  -> cloud-trace-llm-worker (this worker)
  -> OpenAI / Anthropic / Gemini / ...
```

## Why a separate worker

- **Security isolation** — the OpenAI API key (and any future provider keys) lives only in this pod, not next to system orchestration topologies (notifications, trial cron, ...).
- **Independent scaling** — HPA on this worker tracks default-LLM traffic only.
- **Independent deploys** — switching providers or rolling out a connector update doesn't touch system orchestration.
- **Provider switching** — admins reinstall the LLM Application via system instance admin UI; no code change in cloud-backend or user instances.

See `orchesty-cloud/docs/trace-default-llm.md` for the full architecture.

## Prerequisites

- Node.js 20+
- pnpm

## Setup

```sh
cp .env.dist .env
pnpm install
```

Set `OPENAI_API_KEY` in the deployment environment (k8s secret) — it is consumed by the OpenAI Application during install.

## Development

```sh
pnpm start
```

## Build

```sh
pnpm run build
```

## Testing

```sh
pnpm test
```

## Linting

```sh
pnpm run lint
```

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `CRYPT_SECRET` | Encryption secret used by the SDK | `ThisIsNotSoSecret` |
| `ORCHESTY_API_KEY` | API key for Orchesty platform communication | `ThisIsNotSoSecretApiKey` |
| `BACKEND_URL` | URL of the Orchesty backend (system instance) | `http://127.0.0.1:8080` |
| `STARTING_POINT_URL` | URL of the starting point service | `http://127.0.0.1:8080` |
| `WORKER_API_HOST` | Host/port the worker listens on | `http://127.0.0.1:8080` |
| `OPENAI_API_KEY` | OpenAI API key, supplied via k8s secret. Bound to the installed Application by the system instance admin during initial setup. | (none) |

## Registered components

- **`CloudTraceOpenAIApplication`** (extends `@orchesty/connector-open-ai` `OpenAIApplication`)
  - Exposes `syncTrace` sync action: receives a single user-turn payload and returns the LLM response.
- **`OpenAITrace`** connector (`open-ai-trace-connector`)
  - Default model: `gpt-5.4-mini`. Update here when the default model changes.

Future provider failover: install `@orchesty/connector-anthropic`, `@orchesty/connector-gemini` etc. and register additional `Application` + `Connector` pairs in `src/index.ts`. The cloud-backend relay can then route to the desired `appKey` based on the `TRACE_RELAY_DEFAULT_APP_KEY` env (or a fallback list).

## Adding components

Register new components in `src/index.ts`:

```typescript
const myApp = new MyApplication();
container.setApplication(myApp);
container.setNode(new MyConnector(), myApp);
```

**Important:** Always use `container.setNode()` — never the lower-level `container.setConnector()`.
