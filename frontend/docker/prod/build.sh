#!/usr/bin/env bash

set -e -x

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
docker run -ti --rm  \
  -v /Users/kedlas/projects/hanaboso/pipes/pf-typescript:/app \
  -e DEV_UID=$(id -u) \
  -e DEV_GID=1020 \
  -u $(id -u):1020 \
  ${BUILD_IMAGE} \
  bash

docker build -f docker/build/Dockerfile -t ${IMAGE} .
#docker push ${IMAGE}
