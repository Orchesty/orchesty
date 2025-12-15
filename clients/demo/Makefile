# Client-specific vars
CLIENT_DIR="clients/demo"

IMAGE=dkr.hanaboso.net/pipes/demo/php-api:$(TAG)

D=docker
DC_DEV=docker-compose
DE_DEV=docker-compose exec -T
DE_DEVT=docker-compose exec

TOPOLOGY_NAME = benchmark
NODE_NAME = start
RUNS = 1

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		-e 's/{ORCHESTY_API_KEY}/$(shell export LC_CTYPE=C && tr -dc A-Za-z0-9 </dev/urandom | head -c 65)/g' \
		.env.dist > .env

build: .env
	cp .dockerignore ../../.dockerignore
	docker build -f Dockerfile -t ${IMAGE} --pull ../.. || rm ../../.dockerignore
	docker push $(IMAGE)
	rm ../../.dockerignore || true

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml

pull:
	$(DC_DEV) pull --ignore-pull-failures

up: .env pull
	$(DC_DEV) up -d --force-recreate --remove-orphans --build

init: up
	rm -rf pipes-api/var/cache/*
	$(DE_DEV) php-sdk composer install --ignore-platform-reqs
	$(DE_DEV) php-sdk rm -rf var/cache/*
	$(DE_DEV) backend composer install --ignore-platform-reqs
	$(DE_DEV) backend rm -rf var/cache/*
	$(DE_DEV) backend bin/console doctrine:mongodb:schema:update --dm default || true
	$(DE_DEV) backend bin/console doctrine:mongodb:schema:update --dm metrics || true
	$(DE_DEV) backend bin/console mongodb:index:update || true
	$(DC_DEV) run --rm wait-for-it rabbitmq:15672 -t 600
	$(DE_DEV) rabbitmq rabbitmq-plugins enable rabbitmq_consistent_hash_exchange
	$(DE_DEV) rabbitmq rabbitmq-plugins enable rabbitmq_management
	$(DC_DEV) run --rm wait-for-it topology-api:8080 -t 600
	$(DE_DEV) backend bin/console service:install node-sdk node-sdk:8080
	$(DE_DEV) backend bin/console service:install php-sdk php-sdk:80
	$(DE_DEV) backend bin/console topology:install -c -u --force node-sdk:8080
	$(DE_DEV) backend bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d "=" -f2)"
	$(DE_DEV) backend bin/console user:create "$(shell grep 'ORCHESTY_USER' .env | cut -d "=" -f2)" "$(shell grep 'ORCHESTY_PASSWORD' .env | cut -d "=" -f2)"

destroy: .env topology-destroy
	$(DC_DEV) down -v

topology-destroy:
	docker rm -f $$(docker ps -aqf name=topology-\\d+) || true
	sudo rm -rf ../topology/*

applinth-link:
	echo "http://$(shell grep 'DEV_IP' .env | cut -d "=" -f2):81/?u=$$($(DE_DEV) applinth-marketplace-ui /bin/bash -c 'node JWEGen.js')"

mongo-import:
	docker compose cp AuditEntity.json mongo:/tmp
	$(DE_DEV) mongo mongoimport --db "$(shell grep 'MAIN_DB' .env | cut -d "=" -f2)" --collection AuditEntity --file /tmp/AuditEntity.json --jsonArray

run-topology:
	/bin/bash -c 'for i in {1..$(RUNS)}; do echo "Run #$$i: HTTP $$(curl -s -o /dev/null -w "%{http_code}" -X POST http://127.0.0.66:82/topologies/$(TOPOLOGY_NAME)/nodes/$(NODE_NAME)/run-by-name)"; done'


#INSTANCE = xxx
#PASSWORD = xxx
#
#mongo-prod-export:
#	mongodump --gzip -j 1 -o export-import "mongodb://$(INSTANCE):$(PASSWORD)@localhost:27018/$(INSTANCE)?authSource=admin" || true
#	mongodump --gzip -j 1 -o export-import "mongodb://$(INSTANCE):$(PASSWORD)@localhost:27018/$(INSTANCE)-metrics?authSource=admin" || true
#	rm export-import/$(INSTANCE)/ApplicationInstall.bson.gz
#	rm export-import/$(INSTANCE)/ApplicationInstall.metadata.json.gz
#	rm export-import/$(INSTANCE)/cron.bson.gz
#	rm export-import/$(INSTANCE)/cron.metadata.json.gz
#	rm export-import/$(INSTANCE)/limiter.bson.gz
#	rm export-import/$(INSTANCE)/limiter.metadata.json.gz
#	rm export-import/$(INSTANCE)/Webhook.bson.gz
#	rm export-import/$(INSTANCE)/Webhook.metadata.json.gz
#
#mongo-prod-import:
#	mongorestore --gzip -j 1 "mongodb://127.0.0.66:27017/demo"  export-import/$(INSTANCE)
#	mongorestore --gzip -j 1 "mongodb://127.0.0.66:27017/metrics" export-import/$(INSTANCE)-metrics
