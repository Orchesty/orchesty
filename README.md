# PIPES FRAMEWORK

## Setup

1. Create network: `docker network create pipesdemo_default`
2. Init services: `make init-dev`
3. Generate topology: `http://127.0.0.66:80/test/topology/generate/pipesdemo_default`
4. Check running topology: `http://127.0.0.66/topologies/topology/test`
5. Download mock data generator: `docker pull dkr.hanaboso.net/hanaboso/spitter/image:dev`

uncomment frontend 81

docker-compose exec monolith-api php bin/console user:create
docker-compose exec monolith-api php bin/console doctrine:mongodb:schema:create

### Setup pre-commit hook (FE only)

(Only for projects: app-ui, applinth-admin-ui, applinth-marketplace-ui)

In the root run `pnpm install` and `pnpm run prepare-husky`
This will install husky pre-commit hook for FE projects
