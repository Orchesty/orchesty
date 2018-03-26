#!/bin/sh

DB=pipes

curl --retry 5 -XPOST 'http://influxdb:8086/query?db=pipes' --data-urlencode 'q=CREATE RETENTION POLICY "5s" ON '$DB' DURATION 168h REPLICATION 1'
curl --retry 5 -XPOST 'http://influxdb:8086/query?db=pipes' --data-urlencode 'q=CREATE RETENTION POLICY "1m" ON '$DB' DURATION 168h REPLICATION 1'
curl --retry 5 -XPOST 'http://influxdb:8086/query?db=pipes' --data-urlencode 'q=CREATE RETENTION POLICY "30m" ON '$DB' DURATION 168h REPLICATION 1'
curl --retry 5 -XPOST 'http://influxdb:8086/query?db=pipes' --data-urlencode 'q=CREATE RETENTION POLICY "4h" ON '$DB' DURATION 168h REPLICATION 1'

exec /entrypoint.sh "$@"
