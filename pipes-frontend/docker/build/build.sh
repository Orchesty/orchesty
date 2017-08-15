#!/usr/bin/env bash

# Current dir
DIR=$(dirname $0)

# Move to root
cd ${DIR}/../../

# Image build
docker build -f docker/build/Dockerfile -t nodejs-build .