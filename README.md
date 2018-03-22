# PIPES FRAMEWORK

## Setup

1. Create network: `docker network create pipesdemo_default`
1. Init services: `make init-dev`
1. Generate topology: `http://127.0.0.66:80/test/topology/generate/pipesdemo_default`
1. Check running topology: `http://127.0.0.66/topologies/topology/test`
1. Download mock data generator: `docker pull dkr.hanaboso.net/hanaboso/spitter/image:dev`

uncomment frontend 81


docker-compose exec monolith-api php bin/console user:create
docker-compose exec monolith-api php bin/console doctrine:mongodb:schema:create
