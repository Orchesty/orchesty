# PIPES FRAMEWORK Demo

## Setup
1. Start services: `make init`
1. Stop services: `make down`

## Run after first start-up
1. Create user: `docker exec -ti demo_backend_1 php bin/console user:create`

## Frontend
1. Go to: `http://127.0.0.66`


## Add Service
1. Go to: `http://127.0.0.66/services`
2. Click on Create
3. Add new Service:
   1. URL: `node-sdk:8080`
   1. Name: `node-sdk`
