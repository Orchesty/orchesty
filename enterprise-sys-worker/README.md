# Enterprise Sys Worker

Node.js integration worker for the Orchesty platform. Built on the [@orchesty/nodejs-sdk](https://www.npmjs.com/package/@orchesty/nodejs-sdk), it handles connectors, batches, and custom data transformations.

## Prerequisites

- Node.js 20+
- npm

## Setup

```sh
cp .env.dist .env
npm install
```

Edit `.env` and adjust the values for your environment.

## Development

Start the worker with hot-reload (nodemon):

```sh
npm start
```

## Build

Compile TypeScript to `dist/`:

```sh
npm run build
```

## Testing

Run the test suite with coverage:

```sh
npm test
```

## Linting

```sh
npm run lint
```

## Environment Variables

| Variable | Description | Default |
|---|---|---|
| `CRYPT_SECRET` | Encryption secret used by the SDK | `ThisIsNotSoSecret` |
| `ORCHESTY_API_KEY` | API key for Orchesty platform communication | `ThisIsNotSoSecretApiKey` |
| `BACKEND_URL` | URL of the Orchesty backend | `http://127.0.0.1:8080` |
| `STARTING_POINT_URL` | URL of the starting point service | `http://127.0.0.1:8080` |
| `WORKER_API_HOST` | Host/port the worker listens on | `http://127.0.0.1:8080` |

## Project Structure

```
src/
  index.ts                          # Entry point — register components here
  <ServiceName>/
    <ServiceName>Application.ts     # Application (auth provider)
    Connector/
      <ServiceName>GetResource.ts   # One connector per API endpoint
    Batch/
      <ServiceName>ListResources.ts # Paginated data retrieval
    CustomNode/
      <ServiceName>Mapper.ts        # Data transformation
    __tests__/
      <ServiceName>GetResource.ts   # Test file
      Data/
        <ServiceName>GetResource/
          input.json                # Input DTO body
          output.json               # Expected output body
          mock.json                 # HTTP mock
```

## Adding Components

Register all components in `src/index.ts`:

```typescript
// Application
const myApp = new MyApplication();
container.setApplication(myApp);

// Connectors & Batches (pass application as 2nd arg)
container.setNode(new MyConnector(), myApp);
container.setNode(new MyBatch(), myApp);

// Custom Nodes / Mappers (no application needed)
container.setNode(new MyMapper());
```

**Important:** Always use `container.setNode()` — never use `container.setConnector()`, `container.setBatch()`, or `container.setCustomNode()` directly.
