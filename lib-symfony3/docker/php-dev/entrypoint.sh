#!/usr/bin/env bash

getent passwd dev || chown -R ${DEV_UID}:${DEV_GID} /home/dev
getent passwd dev || groupadd dev -g ${DEV_GID} && useradd -m -u ${DEV_UID} -g ${DEV_GID} dev

exec "$@"

