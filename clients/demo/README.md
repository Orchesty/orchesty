# PIPES FRAMEWORK Demo

## Setup

1. Create network: `docker network create pipesdemo_default`
1. Start services: `make init-dev`
1. Stop services: `make down-dev`

## Run after first start-up
1. Create user: `docker-compose -f docker-compose.dev.yml exec monolith-fpm php bin/console user:create`

## Frontend
1. Go to: `http://127.0.0.66:81`

## Develop (deprecated)
1. Generate topology: `http://127.0.0.66:80/test/topology/generate/pipesdemo_default`
1. Check running topology: `http://127.0.0.66/topologies/topology/test`
1. Download mock data generator: `docker pull dkr.hanaboso.net/hanaboso/spitter/image:dev`

