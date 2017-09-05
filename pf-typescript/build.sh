#!/usr/bin/env bash

set -e -x

# Determine the tag
[ -n "$1" ] || (echo "You must pass a tag as the first parameter"; exit 1)
TAG=$1

cd $(dirname $0)

# Remove dependencies
rm -rf node_modules

# Image build
REGISTRY_PREFIX=dkr.hanaboso.net/pipes/pipes
IMAGE=${REGISTRY_PREFIX}/pf-typescript:${TAG}
BUILD_IMAGE=${REGISTRY_PREFIX}/nodejs-build:dev

docker pull ${BUILD_IMAGE}
docker run --rm \
  -v $(pwd):/app \
  -e DEV_UID=$(id -u) \
  -e DEV_GID=$(id -g) \
  -u $(id -u):$(id -g) \
  -v $SSH_AUTH_SOCK:/ssh-agent \
  ${BUILD_IMAGE} \
  bash -c "ssh-add -l && npm install && npm run-script lint && npm run-script build"

chmod +x dist/src/bin/pipes.js

docker build -t ${IMAGE} .
docker push ${IMAGE}
