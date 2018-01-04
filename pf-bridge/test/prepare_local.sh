#!/usr/bin/env bash

# This script should be used only for setting local development machine for running test via CLI.
# It starts docker container with services on which pf-bridge id dependant on.

docker rm -f my-rabbit; docker run -d --hostname my-rabbit -p 15672:15672 -p 5672:5672 --name my-rabbit rabbitmq:3-management-alpine
docker rm -f my-mongo; docker run -d --hostname my-mongo -p 27017:27017 --name my-mongo mongo:3.4
docker rm -f my-redis; docker run --name my-redis -p 6379:6379 -d redis

export RABBITMQ_HOST=localhost
export MONGO_HOST=localhost
export REDIS_HOST=localhost
