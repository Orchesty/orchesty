# Orchesty Worker — Bootstrap Prompt

This document contains everything needed to bootstrap a new Orchesty integration worker from scratch. It is self-contained — no access to an existing worker project or Cursor rules is required.

Follow the steps below in order. Each step includes the exact file contents to create.

---

## Step 1: Initialize the project directory

Create an empty directory for your worker project and navigate into it:

```bash
mkdir my-worker && cd my-worker
```

---

## Step 2: Create `package.json`

Create `package.json` with the Orchesty SDK as the only production dependency. Add connector packages (e.g. `@orchesty/connector-*`) as needed for your integrations.

When adding `@orchesty/connector-*` packages, add an `overrides` section to force all transitive dependencies to use the same SDK version. Without this, connector packages may bundle their own older SDK copy, causing TypeScript type conflicts.

```json
{
  "name": "orchesty-worker",
  "description": "Orchesty integration worker",
  "version": "1.0.0",
  "license": "Apache-2.0",
  "main": "index.js",
  "scripts": {
    "test": "jest --coverage --detectOpenHandles --passWithNoTests",
    "lint": "eslint src --fix",
    "start": "nodemon src/index.ts",
    "build": "rm -rf dist && tsc -p tsconfig.prod.json && copyfiles -u 1 src/**/*.html dist/src/"
  },
  "keywords": [],
  "dependencies": {
    "@orchesty/nodejs-sdk": "^5.0.3",
    "dotenv": "^16.4.7"
  },
  "overrides": {
    "@orchesty/nodejs-sdk": "^5.0.3"
  },
  "devDependencies": {
    "@eslint/eslintrc": "^3.3.3",
    "@eslint/js": "^9.39.2",
    "@hanaboso/eslint-plugin": "^1.2.5",
    "@types/jest": "^30.0.0",
    "@types/node": "^25.0.3",
    "copyfiles": "^2.4.1",
    "eslint": "^9.39.2",
    "jest": "^30.2.0",
    "nodemon": "^3.1.11",
    "ts-jest": "^29.4.6",
    "ts-node": "^10.9.2",
    "typescript": "^5.9.3"
  }
}
```

---

## Step 3: Create `tsconfig.json`

```json
{
    "compilerOptions": {
        "target": "ES2022",
        "module": "commonjs",
        "moduleResolution": "node",
        "rootDir": "./",
        "outDir": "./dist",
        "typeRoots": [
            "node_modules/@types"
        ],
        "allowJs": true,
        "noImplicitAny": true,
        "removeComments": true,
        "esModuleInterop": true,
        "strict": true,
        "experimentalDecorators": true,
        "emitDecoratorMetadata": true,
        "resolveJsonModule": true,
        "skipLibCheck": true
    },
    "include": [
        "src",
        "test"
    ]
}
```

---

## Step 4: Create `tsconfig.prod.json`

```json
{
    "extends": "./tsconfig.json",
    "include": [
        "src"
    ],
    "exclude": [
        "node_modules",
        "test",
        "src/**/__tests__"
    ]
}
```

---

## Step 5: Create `eslint.config.mjs`

```javascript
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import js from '@eslint/js';
import { FlatCompat } from '@eslint/eslintrc';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);
const compat = new FlatCompat({
  baseDirectory: __dirname,
  recommendedConfig: js.configs.recommended,
  allConfig: js.configs.all
});

export default [
  ...compat.extends('plugin:@hanaboso/orchesty'),
  {
    rules: {
    },
  }
];
```

---

## Step 6: Create `jest.config.js`

```javascript
module.exports = {
  preset: 'ts-jest',
  testEnvironment: 'node',
  testMatch: ['**/__tests__/*.ts'],
  roots: ["<rootDir>/src/"],
  setupFiles: ["<rootDir>/.jest/testEnvs.ts"],
  globalSetup: '<rootDir>/.jest/globalSetup.ts',
  setupFilesAfterEnv: ["<rootDir>/.jest/testLifecycle.ts"],
};
```

---

## Step 7: Create `.jest/` test setup files

### `.jest/globalSetup.ts`

```typescript
export default () => {
  process.env.TZ = "UTC";
};
```

### `.jest/testEnvs.ts`

```typescript
import { readFileSync } from 'fs';
const devIp = readFileSync( __dirname + '/../.env')?.toString()?.match("(DEV_IP=)(.*)")?.[2] ?? '';
const devStartingPointUrl = `http://${devIp}:8080`;

process.env.APP_ENV = 'prod';
process.env.CRYPT_SECRET = 'ThisIsNotSoSecret';
process.env.ORCHESTY_API_KEY = 'ThisIsNotSoSecretApiKey';
process.env.BACKEND_URL = `http://${devIp}:8080`;
process.env.STARTING_POINT_URL = devStartingPointUrl;
process.env.WORKER_API_HOST = `http://${devIp}:8080`;
```

### `.jest/testLifecycle.ts`

```typescript
import { createLoggerMockedServer, createMetricsMockedServer } from '@orchesty/nodejs-sdk/dist/test/MockServer';

jest.setTimeout(10000);

beforeAll(async () => {
  createMetricsMockedServer();
  createLoggerMockedServer();
});

beforeEach(async () => {
});

afterAll(async () => {
});
```

---

## Step 8: Create `.env.dist` and `.env`

### `.env.dist` (template, committed to git)

```
DEV_UID={DEV_UID}
DEV_GID={DEV_GID}
DEV_IP=127.0.0.1

APP_PORT=8008
CRYPT_SECRET=ThisIsNotSoSecret
ORCHESTY_API_KEY={ORCHESTY_API_KEY}
BACKEND_URL=http://127.0.0.1:8085
STARTING_POINT_URL=http://127.0.0.1:82
WORKER_API_HOST=http://127.0.0.1:8081
```

### `.env` (local config, NOT committed)

Copy `.env.dist` to `.env` and adjust values for your environment.

| Variable | Description | How to find the value |
|---|---|---|
| `APP_PORT` | Port the worker listens on. Must not conflict with other services (backend uses 8080). | Use `8008` (matches `WORKER_DEFAULT_PORT` in the platform). |
| `CRYPT_SECRET` | Encryption secret — must match the platform's `CRYPT_SECRET`. | Copy from the platform's docker-compose environment. |
| `ORCHESTY_API_KEY` | API key for authenticating with `worker-api` — must match. | Copy from the platform's `.env` (`ORCHESTY_API_KEY`). |
| `BACKEND_URL` | URL of the PHP backend, reachable from the host. | `http://127.0.0.1:<backend-port>` (e.g. `8085` for enterprise). |
| `STARTING_POINT_URL` | URL of the starting-point service. | `http://127.0.0.1:<starting-point-port>` (e.g. `82`). |
| `WORKER_API_HOST` | URL of the `worker-api` service. The SDK uses this to store/retrieve `ApplicationInstall` documents. | `http://127.0.0.1:<worker-api-port>` (e.g. `8081`). |

---

## Step 9: Create `.gitignore`

```
node_modules/
dist/
coverage/
.env
*.js.map
```

---

## Step 10: Create `src/index.ts`

This is the entry point. It initializes the Orchesty SDK container and starts listening for process requests. Register all your applications, connectors, batches, and mappers here.

```typescript
import { config } from 'dotenv';
config();

import { container, initiateContainer, listen } from '@orchesty/nodejs-sdk';

function prepare(): void {
    initiateContainer();

    // ── Applications ──
    // const myApp = new MyApplication();
    // container.setApplication(myApp);

    // ── Connectors & Batches (pass application as 2nd arg) ──
    // container.setNode(new MyConnector(), myApp);
    // container.setNode(new MyBatch(), myApp);

    // ── Custom Nodes / Mappers (no application needed) ──
    // container.setNode(new MyMapper());
}

prepare();
listen();
```

`dotenv` must be loaded **before** the SDK import — the SDK reads environment variables at module load time.

---

## Step 11: Install dependencies

```bash
npm install
```

---

## Step 12: Verify the setup

Build the project to confirm TypeScript compiles without errors:

```bash
npx tsc --noEmit
```

Run the (empty) test suite:

```bash
npm test
```

Both commands should succeed with zero errors.

---

## Step 13: Register the worker in the platform

The Orchesty PHP backend discovers workers via a MongoDB collection called `Sdk`. Each document has a `name` and a `url` pointing to the worker's HTTP server. Without this registration, the platform won't show the worker's applications.

Insert a document into the `Sdk` collection:

```bash
docker compose exec mongo mongosh --quiet --eval '
db.Sdk.insertOne({
  name: "my-worker",
  url: "http://host.docker.internal:8008",
  headers: "[]"
})
' pipes
```

- **`name`** — a unique identifier for this worker (used internally by the platform).
- **`url`** — the address where the PHP backend (running inside Docker) can reach the worker. When the worker runs on the host machine, use `host.docker.internal`. The port must match `APP_PORT` from `.env`.
- **`headers`** — optional JSON-encoded headers sent with every request to the worker.

After inserting, the worker's applications should appear in the platform UI. No backend restart is needed — the `Sdk` collection is read on every request.

---

## Architecture Overview

An Orchesty worker consists of four types of components:

### Application

Handles authentication (OAuth2, Basic Auth, Client Credentials) and builds authenticated HTTP requests. Connectors delegate all auth to the Application.

### Connector

Calls a **single** API endpoint with a **single** `send()` call. Never make more than one HTTP request in a connector. If a workflow requires two API calls, use two connectors as separate topology steps.

### Batch

Like a connector, but handles **paginated** data retrieval. The platform calls `processAction()` repeatedly — once per page — until there are no more pages.

### CustomNode (Mapper / Filter)

Pure data transformation — no HTTP calls, no application dependency. Reshapes data between different payload formats.

### Registration Rules

- Use `container.setApplication(app)` for Applications.
- Use `container.setNode(node, app)` for Connectors and Batches (pass the Application as 2nd argument).
- Use `container.setNode(node)` for CustomNodes (no application needed).
- **Never** use `container.setConnector()`, `container.setBatch()`, or `container.setCustomNode()` directly — they skip dependency injection.

---

## Project Structure Convention

```
src/
  index.ts                          # Entry point — register everything here
  MyService/
    MyServiceApplication.ts         # Application (auth provider)
    Connector/
      MyServiceGetResource.ts       # One connector per API endpoint
      MyServiceCreateResource.ts
    Batch/
      MyServiceListResources.ts     # Paginated data retrieval
    CustomNode/
      MyServiceToOtherMapper.ts     # Data transformation
    __tests__/
      MyServiceGetResource.ts       # Test file (no .test suffix)
      Data/
        MyServiceGetResource/
          input.json                # Input DTO body
          output.json               # Expected output body
          mock.json                 # HTTP mock for 1st call
```

---

## Next Steps

After the project is bootstrapped, start building your integration by creating Applications, Connectors, Batches, and Mappers. For component templates and development patterns, refer to:

- The Cursor rules in `.cursor/rules/` (if available in your project)
- [Orchesty Connector Examples](https://github.com/Orchesty/orchesty-nodejs-connectors/tree/master/lib)
- [Orchesty Docs](https://docs.orchesty.io/category/get-started/)

---

## Common Pitfalls

1. **Port conflicts.** The worker defaults to port `8080` (`APP_PORT` env). This conflicts with the PHP backend which also runs on `8080`. Always set `APP_PORT=8008` (or another free port) in `.env`.

2. **Worker not visible in platform.** The PHP backend does not auto-discover workers. You must register the worker in the MongoDB `Sdk` collection (see Step 13). The `url` must be reachable from inside Docker — use `http://host.docker.internal:<APP_PORT>` when running the worker on the host.

3. **SDK version mismatch.** The platform's `ServiceLocator` expects URL routes with `/sdk/:sdk/` segments (SDK v5+). If you use SDK v4, routes won't match and API calls (install, settings, etc.) will silently fail with empty responses. Always use `@orchesty/nodejs-sdk` v5+.

4. **Connector SDK version conflict.** Pre-built `@orchesty/connector-*` packages may depend on an older SDK version. Add an `overrides` section in `package.json` to force a single SDK version across all dependencies, otherwise TypeScript will report type incompatibilities.

5. **Missing Content-Type header.** If the Application's `getRequestDto()` does not set `Content-Type: application/json`, connectors sending JSON will get `400 Bad Request`. Always verify headers.

6. **Date timezone shifts.** When passing dates between APIs, `new Date(isoString).toISOString()` can shift dates by one day due to UTC conversion. If you only need the date portion, extract it directly: `isoString.substring(0, 10)`.

7. **Binary data.** For file uploads/downloads, convert between Base64 and Buffer:
   - Download: `Buffer.from(response.getBody(), 'binary').toString('base64')`
   - Upload: `Buffer.from(base64String, 'base64')`

8. **Node naming.** Once a node is deployed in a topology, never rename its `getName()` return value — the Orchesty platform references nodes by name.

9. **One connector = one API call.** Never make multiple `send()` calls in a single connector. Use separate topology steps instead.

---

## Useful Links

- [Orchesty Docs](https://docs.orchesty.io/category/get-started/)
- [Connector Examples](https://github.com/Orchesty/orchesty-nodejs-connectors/tree/master/lib)
