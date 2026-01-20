#!/bin/bash

MONGODB_ADMIN_DSN="mongodb://<USERNAME>:<PASSWORD>@percona-ha-cluster-rs0.clusters.svc.cluster.local:27017/?replicaSet=rs0&authSource=admin"
INSTANCE_ID=""
MONGODB_PWD=""
RABBITMQ_PWD=""
NAME=""

# Kontrola, zda jsou všechny povinné proměnné vyplněné
if [[ -z "$INSTANCE_ID" ]] || [[ -z "$MONGODB_PWD" ]] || [[ -z "$RABBITMQ_PWD" ]] || [[ -z "$NAME" ]] || [[ -z "$MONGODB_ADMIN_DSN" ]]; then
    echo "CHYBA: Všechny povinné proměnné musí být vyplněné:" >&2
    echo "  - INSTANCE_ID" >&2
    echo "  - MONGODB_PWD" >&2
    echo "  - RABBITMQ_PWD" >&2
    echo "  - NAME" >&2
    echo "  - MONGODB_ADMIN_DSN" >&2
    exit 1
fi

INSTANCE="instance-${INSTANCE_ID}"

MONGOSH="kubectl exec -n clusters svc/percona-ha-cluster-rs0 -- mongosh $MONGODB_ADMIN_DSN"
RABBITMQCTL="kubectl exec -n clusters svc/rabbitmq-cluster -- rabbitmqctl"

echo "Creating mongodb user..."
$MONGOSH --eval "db.getSiblingDB('admin').createUser({user: '$INSTANCE', pwd: '$MONGODB_PWD', roles: [{role: 'dbOwner', db: '$INSTANCE'}, {role: 'dbOwner', db: '${INSTANCE}-metrics'}], customData: { ocInstanceDisplayName: '$NAME'}})"

echo "Create RabbitMQ vhost and user"
$RABBITMQCTL add_vhost $INSTANCE --description "'ocInstanceDisplayName: $NAME'"
$RABBITMQCTL add_user $INSTANCE $RABBITMQ_PWD
$RABBITMQCTL set_user_tags $INSTANCE monitoring
$RABBITMQCTL set_permissions --vhost $INSTANCE default_user_z2lLQkOZkUNAfvCSHeS "'.*'" "'.*'" "'.*'"
$RABBITMQCTL set_permissions --vhost $INSTANCE $INSTANCE "'.*'" "'.*'" "'.*'"