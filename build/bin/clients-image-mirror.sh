#!/bin/bash

registry="dkr.hanaboso.net"

clients='[
    {"from": "dkr.hanaboso.net/pipes/pipes/applinth:2.0", "to": "dkr.hanaboso.net/fabis/applinth/applinth:2.0"},
    {"from": "dkr.hanaboso.net/pipes/pipes/applinth-marketplace-ui:2.0", "to": "dkr.hanaboso.net/fabis/applinth/applinth-marketplace-ui:2.0"},
    {"from": "dkr.hanaboso.net/pipes/pipes/frontend-enterprise:master", "to": "dkr.hanaboso.net/oxy/images/frontend-enterprise:3.0-rc1"},
    {"from": "dkr.hanaboso.net/pipes/pipes/backend-enterprise:master", "to": "dkr.hanaboso.net/oxy/images/backend-enterprise:3.0-rc1"}
]'

echo "$clients" | jq -c '.[]' | while read -r obj; do
    from=$(echo "$obj" | jq -r '.from')
    to=$(echo "$obj" | jq -r '.to')

    echo "Copying $from -> $to"
    docker buildx imagetools create -t "$to" "$from"
done