# Applinth Admin UI

## Development

Start frontend dev server (vue cli server) and local fake backend

```
pnpm run dev
```

Local server will be running on:

- http://localhost:8080 for frontend
- http://localhost:4010 for fake backend
- http://localhost:4011 for swagger ui

### Applications metadata

Download with npm command

```
pnpm run download:apps-metadata
```

### Run with docker

Start app and backend via Makefile

```
make start-mock
```

App will be running here:

- http://127.0.0.42:83 for frontend
- http://127.0.0.42:4010 for fake backend

### Run with local backend

Start app and backend via Makefile

```
make init
```

App will be running here:

- http://127.0.0.42:83 for frontend
- http://127.0.0.42:3000 for local console API backend

### Generate fixture data

Drop collections and create new fixture data

```
make generate-fixture-data
```
