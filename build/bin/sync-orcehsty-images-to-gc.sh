#!/bin/bash

set -e

TAG=$1

IMAGES="\
	backend \
	bridge \
	counter \
	cron \
	detector \
	frontend \
	limiter \
	fluentd \
	pf-bridge \
	starting-point \
	topology-api \
	worker-api \
"

TOKEN=$(gcloud auth print-access-token)

for I in $IMAGES; do
	echo $I
	docker run -t --rm quay.io/skopeo/stable:latest copy --dest-creds oauth2accesstoken:${TOKEN} docker://orchesty/${I}:${TAG} docker://europe-west1-docker.pkg.dev/orchesty-cloud-prod/orchesty/${I}:${TAG}
done
