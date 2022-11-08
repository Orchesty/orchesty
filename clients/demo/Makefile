# Client-specific vars
CLIENT_DIR="clients/demo"

TAG?=dev
IMAGE=dkr.hanaboso.net/pipes/demo/php-api:$(TAG)

# Generic vars
MINIMAL=docker-compose.minimal.yml
DEV_YML?=$(MINIMAL) -f docker-compose.dev.yml

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env.dist | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		-e 's/{ORCHESTY_API_KEY}/$(shell tr -dc A-Za-z0-9 </dev/urandom | head -c 20)/g' \
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

up: .env pull .lo0-up
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
	$(DE_DEV) backend bin/console service:install node-sdk node-sdk:8080
	$(DE_DEV) backend bin/console service:install php-sdk php-sdk:80
	$(DE_DEV) backend bin/console topology:install -c -u --force node-sdk:8080

destroy: .env .lo0-down
	$(DC_DEV) down -v

init-minimal:
	make init DEV_YML=$(MINIMAL)

destroy-minimal:
	make destroy DEV_YML=$(MINIMAL)
