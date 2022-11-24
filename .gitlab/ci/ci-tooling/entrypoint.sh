#!/bin/sh

echo "${GCLOUD_SA_KEY}" > /tmp/gcloud-sa-key.json

if [ -n "${GCLOUD_ACTIVATE_SA}" ]; then
  gcloud auth activate-service-account --key-file=/tmp/gcloud-sa-key.json
fi

exec "$@"
