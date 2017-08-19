#!/usr/bin/env bash

set -e -x

# Determine the tag
[ -n "$1" ] || (echo "You must pass a tag as the first parameter"; exit 1)
TAG=$1


# Current dir
cd $(dirname $0)

# Install dependencies and create prod cache
BUILD_IMAGE=dkr.hanaboso.net/pipes/pipes/php-dev:dev
docker pull ${BUILD_IMAGE}
docker run --rm \
  -v $(pwd)/../../:/srv/project \
  -v $(pwd)/../../../../../:/srv/libs \
  -v /home/dev/.composer:/home/dev/.composer \
  -e DEV_UID=$(id -u) \
  -e DEV_GID=$(id -g) \
  -v $SSH_AUTH_SOCK:/ssh-agent \
  ${BUILD_IMAGE} \
  bash -c "composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-scripts && sudo bin/console cache:warmup -e prod"

# Image build
IMAGE=dkr.hanaboso.net/pipes/pipes/cl-demo-mapper:${TAG}
docker build -t ${IMAGE} .
docker push ${IMAGE}
