IMAGE=dkr.hanaboso.net/pipes/demo/php-api:$(TAG)

DC = docker compose
DE = docker compose exec -T
DCB = docker compose exec -T backend
DCBE = docker compose exec -T backend-enterprise
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
	$(DCB) bin/console service:install sys-worker sys-worker:8080
	$(DCB) bin/console service:install node-sdk node-sdk:8080
	$(DCB) bin/console service:install node-sdk-tunnel '' tunnel
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
	$(DC) cp mockoon/AuditEntity.json mongo:/tmp
	$(DCM) mongoimport --db "$(shell grep 'MAIN_DB' .env | cut -d "=" -f2)" --collection AuditEntity --file /tmp/AuditEntity.json --jsonArray
	$(DCBE) sh -c '\
		curl -s -X POST "http://localhost/api/user/setup" \
			-H "Content-Type: application/json" \
			-d "{\"email\":\"$(shell grep ORCHESTY_USER .env | cut -d= -f2)\",\"password\":\"$(shell grep ORCHESTY_PASSWORD .env | cut -d= -f2)\"}" > /dev/null && \
		printf "\033[32mUser was successfully created.\033[0m\n"'
	$(DCBE) sh -c '\
		TOKEN=$$(curl -s -X POST http://localhost/api/user/login \
			-H "Content-Type: application/json" \
			-H "Accept: application/json" \
			-d "{\"email\":\"$(shell grep ORCHESTY_USER .env | cut -d= -f2)\",\"password\":\"$(shell grep ORCHESTY_PASSWORD .env | cut -d= -f2)\"}" \
			| grep -o "\"token\":\"[^\"]*\"" | sed "s/\"token\":\"//;s/\"//") && \
		curl -s -X POST "http://localhost/api/applications/smtp?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/smtp?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"authorization_form\":{\"connectionUrl\":\"smtp://mailhog:1025\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/smtp/changeState?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication smtp (sys-worker) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/open-ai?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/open-ai?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"authorization_form\":{\"api_key\":\"$(shell grep OPENAI_API_KEY .env | cut -d= -f2)\",\"organization_id\":\"\",\"project_id\":\"\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/open-ai/changeState?sdk=sys-worker" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication open-ai (sys-worker) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/http-status?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"limiter_form\":{\"useLimit\":true,\"value\":\"40\",\"time\":\"60\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status/changeState?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication http-status (node-sdk) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/http-status?sdk=node-sdk-tunnel" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status?sdk=node-sdk-tunnel" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"limiter_form\":{\"useLimit\":true,\"value\":\"40\",\"time\":\"60\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status/changeState?sdk=node-sdk-tunnel" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication http-status (node-sdk-tunnel) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/http-status?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"limiter_form\":{\"useLimit\":true,\"value\":\"20\",\"time\":\"60\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/http-status/changeState?sdk=php-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication http-status (php-sdk) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/mysql?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/mysql?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"authorization_form\":{\"host\":\"mysql\",\"port\":\"3306\",\"user\":\"root\",\"password\":\"$(shell grep MYSQL_ROOT_PASSWORD .env | cut -d= -f2)\",\"database\":\"$(shell grep MYSQL_DATABASE .env | cut -d= -f2)\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/mysql/changeState?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication mysql (node-sdk) was successfully installed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/applications/beeceptor?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/beeceptor?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"authorization_form\":{\"url\":\"http://mockoon:3000\"}}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/applications/beeceptor/changeState?sdk=node-sdk" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"enabled\":true}" > /dev/null && \
		printf "\033[32mApplication beeceptor (node-sdk) was successfully installed.\033[0m\n"'
	$(DCBE) sh -c '\
		TOKEN=$$(curl -s -X POST http://localhost/api/user/login \
			-H "Content-Type: application/json" \
			-H "Accept: application/json" \
			-d "{\"email\":\"$(shell grep ORCHESTY_USER .env | cut -d= -f2)\",\"password\":\"$(shell grep ORCHESTY_PASSWORD .env | cut -d= -f2)\"}" \
			| grep -o "\"token\":\"[^\"]*\"" | sed "s/\"token\":\"//;s/\"//") && \
		curl -s -X PUT "http://localhost/api/notifications/subscriptions" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"event_type\":\"topology_failed_message\",\"channel\":\"email\",\"enabled\":true}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/notifications/subscriptions" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"event_type\":\"topology_failed\",\"channel\":\"email\",\"enabled\":true}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/notifications/subscriptions" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"event_type\":\"topology_slow\",\"channel\":\"email\",\"enabled\":true}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/notifications/subscriptions" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"event_type\":\"limit_overflow\",\"channel\":\"email\",\"enabled\":true}" > /dev/null && \
		curl -s -X PUT "http://localhost/api/notifications/subscriptions" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"event_type\":\"limit_recovered\",\"channel\":\"email\",\"enabled\":true}" > /dev/null && \
		printf "\033[32mNotification subscriptions were successfully created.\033[0m\n" && \
		curl -s -X PUT "http://localhost/api/platform-services/trace-ai-provider" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{\"applicationKey\":\"open-ai\",\"sdk\":\"sys-worker\"}" > /dev/null && \
		printf "\033[32mPlatform service trace-ai-provider was successfully bound to open-ai.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/topologies/by-name/mysql-to-beeceptor-categories/nodes/beeceptor.category%3Acreated/webhook-config/subscribe" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		printf "\033[32mWebhook beeceptor.category:created on mysql-to-beeceptor-categories subscribed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/topologies/by-name/mysql-to-beeceptor-categories/nodes/beeceptor.category%3Aupdated/webhook-config/subscribe" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		printf "\033[32mWebhook beeceptor.category:updated on mysql-to-beeceptor-categories subscribed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/topologies/by-name/mysql-to-beeceptor-products/nodes/beeceptor.product%3Acreated/webhook-config/subscribe" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		printf "\033[32mWebhook beeceptor.product:created on mysql-to-beeceptor-products subscribed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/topologies/by-name/mysql-to-beeceptor-products/nodes/beeceptor.product%3Aupdated/webhook-config/subscribe" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		printf "\033[32mWebhook beeceptor.product:updated on mysql-to-beeceptor-products subscribed.\033[0m\n" && \
		curl -s -X POST "http://localhost/api/topologies/by-name/mysql-to-beeceptor-products/nodes/beeceptor.productCategory%3Aall/webhook-config/subscribe" \
			-H "Content-Type: application/json" -H "Accept: application/json" -H "Authorization: $$TOKEN" \
			-d "{}" > /dev/null && \
		printf "\033[32mWebhook beeceptor.productCategory:all on mysql-to-beeceptor-products subscribed.\033[0m\n"'
	$(DCBE) sh -c '\
		curl -s -X POST "http://starting-point:8080/topologies/http-status/nodes/start/run-by-name" > /dev/null && \
		printf "\033[32mTopology http-status was successfully triggered.\033[0m\n" && \
		curl -s -X POST "http://starting-point:8080/topologies/json-placeholder-to-beeceptor-posts/nodes/start/run-by-name" > /dev/null && \
		printf "\033[32mTopology json-placeholder-to-beeceptor-posts was successfully triggered.\033[0m\n" && \
		sleep 5 && \
		curl -s -X POST "http://starting-point:8080/topologies/mysql-to-beeceptor-categories/nodes/start/run-by-name" > /dev/null && \
		printf "\033[32mTopology mysql-to-beeceptor-categories was successfully triggered.\033[0m\n" && \
		curl -s -X POST "http://starting-point:8080/topologies/mysql-to-beeceptor-products/nodes/product/run-by-name" > /dev/null && \
		printf "\033[32mTopology mysql-to-beeceptor-products (product) was successfully triggered.\033[0m\n" && \
		sleep 30 && \
		curl -s -X POST "http://starting-point:8080/topologies/mysql-to-beeceptor-products/nodes/product-category/run-by-name" > /dev/null && \
		printf "\033[32mTopology mysql-to-beeceptor-products (product-category) was successfully triggered.\033[0m\n"'

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
#	$(DCM) mongosh demo --eval 'var u = db.User.findOne({email: "$(shell grep ORCHESTY_USER .env | cut -d= -f2)"}); if (u) { if (!db.Group.findOne({name: "super_admin"})) { db.Group.insertOne({name: "super_admin", level: NumberInt(0), users: []}); } db.Group.updateOne({name: "super_admin"}, {$$addToSet: {users: DBRef("User", u._id)}}); print("User added to super_admin group."); } else { print("User not found."); }'
#	$(DCB) bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d '=' -f2)"
#	$(DCB) bin/console service:install node-sdk node-sdk:8080
#	$(DCN) node importedDatabaseFix.js
#	$(DCB) bin/console mongodb:index:update
#	$(DC) up -d --force-recreate limiter
