#!/bin/bash

registry="dkr.hanaboso.net"

clients='[
    {"from": "dkr.hanaboso.net/pipes/pipes/applinth:2.0", "to": "dkr.hanaboso.net/fabis/applinth/applinth:2.0"},
    {"from": "dkr.hanaboso.net/pipes/pipes/applinth-marketplace-ui:2.0", "to": "dkr.hanaboso.net/fabis/applinth/applinth-marketplace-ui:2.0"}
]'

echo "$clients" | jq -c '.[]' | while read -r obj; do
    from=$(echo "$obj" | jq -r '.from')
    to=$(echo "$obj" | jq -r '.to')

    imageFrom=${from//$registry/""}
    imageTo=${to//$registry/""}

    docker pull $registry$imageFrom
    docker tag $registry$imageFrom $registry$imageTo
    docker push $registry$imageTo
done