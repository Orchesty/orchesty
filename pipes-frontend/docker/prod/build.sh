#!/usr/bin/env bash

# Current dir
DIR=$(dirname $0)

echo ${DIR}

# Move to root
cd ${DIR}/../../

# Remove dependencies
rm -rf node_modules

# Image build
docker/build/build.sh
docker run --volume $SSH_AUTH_SOCK:/ssh-agent --env SSH_AUTH_SOCK=/ssh-agent nodejs-build ssh-add -l && npm install --production
docker build -f docker/dev/Dockerfile -t frontend .