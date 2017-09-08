#!/usr/bin/env bash

set -e -x

# Is SSH agent forwarding ready?
[ -n "$SSH_AUTH_HOST" ] || (echo "SSH_AUTH_HOST variable must be set"; exit 1)

# Determine the tag
[ -n "$1" ] || (echo "You must pass a tag as the first parameter"; exit 1)
TAG=$1

# Current dir
DIR=$(dirname $0)

echo ${DIR}

# Move to root
cd ${DIR}/../../

# Remove dependencies
rm -rf node_modules

# Image build
REGISTRY_PREFIX=dkr.hanaboso.net/pipes/pipes
IMAGE=${REGISTRY_PREFIX}/frontend:${TAG}
BUILD_IMAGE=${REGISTRY_PREFIX}/nodejs-build:dev

docker pull ${BUILD_IMAGE}
docker run --rm \
  -v $(pwd):/app \
  -e DEV_UID=$(id -u) \
  -e DEV_GID=$(id -g) \
  -e SSH_AUTH_SOCK=/tmp/ssh-agent \
  -u $(id -u):$(id -g) \
  ${BUILD_IMAGE} \
  bash -c "socat UNIX-LISTEN:/tmp/ssh-agent,reuseaddr,fork TCP:${SSH_AUTH_HOST}:2214 & sleep .2 && ssh-add -l && npm install && npm run build"

docker build -f docker/build/Dockerfile -t ${IMAGE} .
docker push ${IMAGE}
