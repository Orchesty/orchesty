# Applinth Admin UI

## Development

Start frontend dev server (vue cli server) and local fake backend
```
npm run dev
```

Local server will be running on:
- http://localhost:8080 for frontend
- http://localhost:4010 for fake backend

### Run with docker
Start app and backend via Makefile
```
make init
make start-mock
```

App will be running here:
- http://127.0.0.42:83 FE
- http://127.0.0.42:4010 fake BE
