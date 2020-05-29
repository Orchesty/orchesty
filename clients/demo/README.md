# PIPES FRAMEWORK Demo

## Setup

1. Start services: `make init-dev`
1. Stop services: `make down-dev`

## Run after first start-up
1. Create user: `docker-compose -f docker-compose.dev.yml exec backend php bin/console user:create`

## Frontend
1. Go to: `http://127.0.0.66:81`
