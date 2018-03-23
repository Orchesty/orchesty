#!/usr/bin/env bash

set -e -x

# Determine the tag
[ -n "$1" ] || (echo "You must pass a tag as the first parameter"; exit 1)
[ -n "$SSH_AUTH_HOST" ] || (echo "SSH_AUTH_HOST variable must be set"; exit 1)
TAG=$1

cd $(dirname $0)

# Remove dependencies
rm -rf node_modules

# Image build
REGISTRY_PREFIX=dkr.hanaboso.net/pipes/pipes
IMAGE=${REGISTRY_PREFIX}/pf-bridge:${TAG}
BUILD_IMAGE=${REGISTRY_PREFIX}/nodejs-build:dev

docker pull ${BUILD_IMAGE}
docker run --rm \
  -v $(pwd):/app \
  -e DEV_UID=$(id -u) \
  -e DEV_GID=$(id -g) \
  -e SSH_AUTH_SOCK=/tmp/ssh-agent \
  -u $(id -u):$(id -g) \
  ${BUILD_IMAGE} \
  bash -c "socat UNIX-LISTEN:/tmp/ssh-agent,reuseaddr,fork TCP:${SSH_AUTH_HOST}:2214 & sleep .2 && ssh-add -l && npm install && npm run-script lint && npm run-script build"

chmod +x dist/src/bin/pipes.js

docker build -t ${IMAGE} .
#docker push ${IMAGE}
