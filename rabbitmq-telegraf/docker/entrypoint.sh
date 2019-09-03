#!/usr/bin/env bash

DEV_UID=$(id -u)
DEV_GID=$(id -g)

getent passwd dev || groupadd dev -g ${DEV_GID} && useradd -m -u ${DEV_UID} -g ${DEV_GID} dev
export HOME=/home/dev

exec "$@"