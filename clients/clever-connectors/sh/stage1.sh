#!/usr/bin/env bash

USER=$1

if [ "${USER}" == "" ]; then
    echo "Zadejte uživatele."
    exit
fi

ssh -L27017:localhost:27017 -L5601:localhost:5601 -L 15672:localhost:15672 $USER@cm-swarm01n01