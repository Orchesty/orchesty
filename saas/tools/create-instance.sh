#!/bin/bash

NAME=$1
INSTANCE_ID=$(pwgen -1s 10 | tr A-Z a-z)
INSTANCE="instance-${INSTANCE_ID}"
MONGODB_PWD=$(pwgen -1s 16)
RABBITMQ_PWD=$(pwgen -1s 16)
ORCHESTY_PWD=$(pwgen -1s 16)
ORCHESTY_USR="pipes@hanaboso.com"

if ! [ -x "$(command -v jq)" ]; then
  echo 'Error: jq is not installed.' >&2
  exit 1
fi

if ! [ -x "$(command -v pwgen)" ]; then
  echo 'Error: pwgen is not installed.' >&2
  exit 1
fi

if [[ -z "$NAME" || -z "$MONGODB_ADMIN_DSN" || -z "$RABBITMQ_ADMIN_DSN" ]]; then
    echo "Usage:"
    echo
    echo "$0 <human-readable-name>"
    echo
    echo "Requred ENVs (copy+paste ready):"
    echo "export MONGODB_ADMIN_DSN= ; export RABBITMQ_ADMIN_DSN="
    exit 1
fi

echo "Cloud resources for instance with id '${INSTANCE_ID}' and name '${NAME}' will be created, press enter to continue"
echo
echo "WARNING: if any of the following steps fail, you have to reconciliate using your brain and fingers!"
read

MONGOSH="kubectl exec -n default  svc/mongos -- mongosh $MONGODB_ADMIN_DSN"
RABBITMQCTL="gcloud compute ssh rabbitmq-c1n1 --project=orchesty-cloud-prod --zone=europe-west1-b --tunnel-through-iap -- sudo rabbitmqctl"

echo "Creating mongodb user..."
$MONGOSH --eval "db.getSiblingDB('admin').createUser({user: '$INSTANCE', pwd: '$MONGODB_PWD', roles: [{role: 'dbOwner', db: '$INSTANCE'}, {role: 'dbOwner', db: '${INSTANCE}-metrics'}], customData: { ocInstanceDisplayName: '$NAME'}})"

echo "Create RabbitMQ vhost and user"
$RABBITMQCTL add_vhost $INSTANCE --description "'ocInstanceDisplayName: $NAME'"
$RABBITMQCTL add_user $INSTANCE $RABBITMQ_PWD
$RABBITMQCTL set_user_tags $INSTANCE monitoring
$RABBITMQCTL set_permissions --vhost $INSTANCE admin "'.*'" "'.*'" "'.*'"
$RABBITMQCTL set_permissions --vhost $INSTANCE $INSTANCE "'.*'" "'.*'" "'.*'"

echo "Creating Kubernetes namespace..."
kubectl create ns $INSTANCE
kubectl label ns $INSTANCE oc-instance-displayname="$NAME"
kubectl -n cloud-control get secret hanaboso -ojson | jq 'del(.metadata.namespace)' | kubectl apply -f -

echo
echo Secrets:
echo
echo UI: https://ui-$INSTANCE_ID.eu1.cloud.orchesty.io / $ORCHESTY_USR / $ORCHESTY_PWD
echo mongodb DSN: mongodb://$INSTANCE:$MONGODB_PWD@mongos.default.svc.cluster.local/$INSTANCE?authSource=admin
echo mongodb metrics DSN: mongodb://$INSTANCE:$MONGODB_PWD@mongos.default.svc.cluster.local/$INSTANCE-metrics?authSource=admin
echo rabbitmq dsn: amqp://$INSTANCE:$RABBITMQ_PWD@rabbitmq-proxy.default.svc.cluster.local:5672/$INSTANCE
echo
echo Usefull Commands:
echo
echo kubectl -n $INSTANCE exec -ti <backend-pod> bin/console u:c $ORCHESTY_USR $ORCHESTY_PWD
