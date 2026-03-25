IMAGE=dkr.hanaboso.net/pipes/demo/php-api:$(TAG)

DC = docker compose
DE = docker compose exec -T
DCB = docker compose exec -T backend
DCM = docker compose exec -T mongo
DCN = docker compose exec -T node-sdk
DCP = docker compose exec -T php-sdk
DCR = docker compose exec -T rabbitmq
DCW = docker compose run --rm wait-for-it

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
	$(DC) pull --ignore-pull-failures

up: .env pull
	$(DC) up -d --force-recreate --remove-orphans --build

init: up
	rm -rf pipes-api/var/cache/*
	$(DCP) composer install --ignore-platform-reqs
	$(DCP) rm -rf var/cache/*
	$(DCB) composer install --ignore-platform-reqs
	$(DCB) rm -rf var/cache/*
	$(DCB) bin/console doctrine:mongodb:schema:update --dm default || true
	$(DCB) bin/console doctrine:mongodb:schema:update --dm metrics || true
	$(DCB) bin/console mongodb:index:update || true
	docker pull dkr.hanaboso.net/pipes/pipes/pf-bridge:new-ui
	$(DCW) rabbitmq:15672 -t 600
	$(DCR) rabbitmq-plugins enable rabbitmq_consistent_hash_exchange
	$(DCR) rabbitmq-plugins enable rabbitmq_management
	$(DCW) topology-api:8080 -t 600
	$(DCB) bin/console service:install node-sdk node-sdk:8080
	$(DCB) bin/console service:install php-sdk php-sdk:80
	$(DCB) bin/console topology:install -c -u --force ''
	#$(DCB) bin/console topology:install -c -u -m --force ''
	$(DCB) bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d "=" -f2)"

destroy: .env topology-destroy
	$(DC) down -v

topology-destroy:
	docker rm -f $$(docker ps -aqf name=topology-\\d+) || true
	sudo rm -rf ../topology/*

applinth-link:
	echo "http://$(shell grep 'DEV_IP' .env | cut -d "=" -f2):81/?u=$$($(DE) applinth-marketplace-ui /bin/bash -c 'node JWEGen.js')"

init-more: init
	$(DCB) bin/console user:create "$(shell grep 'ORCHESTY_USER' .env | cut -d "=" -f2)" "$(shell grep 'ORCHESTY_PASSWORD' .env | cut -d "=" -f2)"
	$(DCB) sh -c '\
		TOKEN=$$(curl -s -X POST http://localhost/api/user/login \
			-H "Content-Type: application/json" \
			-H "Accept: application/json" \
			-d "{\"email\":\"$(shell grep ORCHESTY_USER .env | cut -d= -f2)\",\"password\":\"$(shell grep ORCHESTY_PASSWORD .env | cut -d= -f2)\"}" \
			| grep -o "\"token\":\"[^\"]*\"" | sed "s/\"token\":\"//;s/\"//") && \
		curl -s -X POST "http://localhost/api/applications/http-status?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"limiter_form\":{\"useLimit\":true,\"value\":\"200\",\"time\":\"60\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status/changeState?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication http-status (node-sdk) was successfully installed.\033[0m\n"'
	$(DCB) sh -c '\
		TOKEN=$$(curl -s -X POST http://localhost/api/user/login \
			-H "Content-Type: application/json" \
			-H "Accept: application/json" \
			-d "{\"email\":\"$(shell grep ORCHESTY_USER .env | cut -d= -f2)\",\"password\":\"$(shell grep ORCHESTY_PASSWORD .env | cut -d= -f2)\"}" \
			| grep -o "\"token\":\"[^\"]*\"" | sed "s/\"token\":\"//;s/\"//") && \
		curl -s -X POST "http://localhost/api/applications/http-status?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"limiter_form\":{\"useLimit\":true,\"value\":\"100\",\"time\":\"60\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status/changeState?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication http-status (php-sdk) was successfully installed.\033[0m\n"'

mongo-import:
	$(DC) cp AuditEntity.json mongo:/tmp
	$(DE) mongo mongoimport --db "$(shell grep 'MAIN_DB' .env | cut -d "=" -f2)" --collection AuditEntity --file /tmp/AuditEntity.json --jsonArray

run-topology:
	/bin/bash -c 'for i in {1..$(RUNS)}; do echo "Run #$$i: HTTP $$(curl -s -o /dev/null -w "%{http_code}" -X POST http://127.0.0.66:82/topologies/$(TOPOLOGY_NAME)/nodes/$(NODE_NAME)/run-by-name)"; done'

migrate-topology:
	$(DCB) bin/console topology:migrate -f

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
#	$(DC) up -d --force-recreate mongo
#	$(DC) cp export-import mongo:/tmp/export-import
#	$(DCB) bin/console doctrine:mongodb:schema:update --dm default || true
#	$(DCB) bin/console doctrine:mongodb:schema:update --dm metrics || true
#	$(DCM) mongosh demo --eval 'db.getCollectionNames().forEach(c => db[c].dropIndexes())'
#	$(DCM) mongosh metrics --eval 'db.getCollectionNames().forEach(c => db[c].dropIndexes())'
#	$(DCM) mongorestore --gzip --noIndexRestore --numParallelCollections $$(($$(nproc)*2/3)) --numInsertionWorkersPerCollection $$(($$(nproc)*2/3)) --writeConcern '{w:0}' 'mongodb://localhost/demo' /tmp/export-import/$(INSTANCE)
#	$(DCM) mongorestore --gzip --noIndexRestore --numParallelCollections $$(($$(nproc)*2/3)) --numInsertionWorkersPerCollection $$(($$(nproc)*2/3)) --writeConcern '{w:0}' 'mongodb://localhost/metrics' /tmp/export-import/$(INSTANCE)-metrics
#	$(DCB) bin/console user:create "$(shell grep 'ORCHESTY_USER' .env | cut -d '=' -f2)" "$(shell grep 'ORCHESTY_PASSWORD' .env | cut -d '=' -f2)"
#	$(DCB) bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d '=' -f2)"
#	$(DCB) bin/console service:install node-sdk node-sdk:8080
#	$(DCN) node importedDatabaseFix.js
#	$(DCB) bin/console mongodb:index:update
#	$(DC) up -d --force-recreate limiter
