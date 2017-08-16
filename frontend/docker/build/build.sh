#!/usr/bin/env bash

set -e -x

# Determine the tag
[ -n "$1" ] || (echo "You must pass a tag as the first parameter"; exit 1)
TAG=$1

cd $(dirname $0)

# Image build
IMAGE=dkr.hanaboso.net/pipes/pipes/nodejs-build:${TAG}
docker build -t ${IMAGE} .
docker push ${IMAGE}
