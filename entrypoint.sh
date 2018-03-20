#!/bin/bash

set -ex

if [ "$MONGO_HOST" != "" ]; then

    if [ "$MONGO_USER" != "" ]; then
        mongo --host="$MONGO_HOST" "$MONGO_DATABASE" -u "$MONGO_USER" -p "$MONGO_PASS" --authenticationDatabase=admin --eval="db.createCollection( '$MONGO_COLLECTION', { capped: true, size: '$MONGO_SIZE_COLLETION' } );"
    else
        mongo --host="$MONGO_HOST" "$MONGO_DATABASE" --eval="db.createCollection( '$MONGO_COLLECTION', { capped: true, size: '$MONGO_SIZE_COLLETION' } );"
    fi;

fi

exec /usr/local/bin/docker-entrypoint "$@"