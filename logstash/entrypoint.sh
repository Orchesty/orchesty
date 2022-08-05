#!/bin/bash

cd /usr/share/logstash/pipeline

if [ "$LOG_STORAGE" = "mongodb" ] ; then
  echo "Using config for MongoDB"
  cp ../pipeline-variants/logstash_mongo.conf .

elif [ "$LOG_STORAGE" = "elasticsearch" ]; then
  echo "Using config for Elasticsearch"
  cp ../pipeline-variants/logstash_elastics.conf .

elif [ "$LOG_STORAGE" = "google" ]; then
  echo "Using config for Google Cloud Logs"
  cp ../pipeline-variants/logstash_google.conf .
  echo "ERROR: LOG_STORAGE google not supported in current version"
  exit 1

else
  echo "ERROR: Unsupported log storage: ${LOG_STORAGE}"
  exit 1
fi

exec /usr/local/bin/docker-entrypoint
