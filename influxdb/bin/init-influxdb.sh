#!/usr/bin/env bash

echo 'Creating retention policy.'

if [ ! -z "$INFLUXDB_DB" ]; then
    influx -execute 'CREATE RETENTION POLICY "5s" ON '$INFLUXDB_DB' DURATION 168h REPLICATION 1'
    influx -execute 'CREATE RETENTION POLICY "1m" ON '$INFLUXDB_DB' DURATION 168h REPLICATION 1'
    influx -execute 'CREATE RETENTION POLICY "30m" ON '$INFLUXDB_DB' DURATION 168h REPLICATION 1'
    influx -execute 'CREATE RETENTION POLICY "4h" ON '$INFLUXDB_DB' DURATION 168h REPLICATION 1'
fi