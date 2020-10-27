# Client-specific vars
CLIENT_DIR="clients/demo"

TAG?=dev
IMAGE=dkr.hanaboso.net/pipes/demo/php-api:$(TAG)

# Generic vars

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env && \
	../../build/bin/map-branch-image-tags.sh ./branch-image-tag-map $(shell git rev-parse --symbolic-full-name --abbrev-ref HEAD) >> .env

docker-pull-dev:
	$(DC_DEV) pull

up: .env
	$(DC_DEV) pull
	$(DC_DEV) up -d --force-recreate --remove-orphans

init-dev: .env
	$(DC_DEV) pull
	rm -rf pipes-api/var/cache/*
	$(DC_DEV) up -d --force-recreate --remove-orphans
	$(DE_DEV) php-sdk composer install --no-suggest
	$(DE_DEV) php-sdk rm -rf var/cache/*

destroy-dev:
	$(DC_DEV) down -v

init-mongo:
	docker-compose -f docker-compose.mongo.yml pull
	docker-compose -f docker-compose.mongo.yml up -d --force-recreate --remove-orphans

destroy-mongo:
	docker-compose -f docker-compose.mongo.yml down -v

composer-update-dev:
	$(DE_DEV) php-sdk composer update --no-suggest

build: .env
	cp .dockerignore ../../.dockerignore
	docker build -f Dockerfile -t ${IMAGE} --pull ../.. || rm ../../.dockerignore
	docker push $(IMAGE)
	rm ../../.dockerignore || true

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml
	# Comment/uncomment DEV/STAGE sections
	sed -r '/# BEGIN DEV$$/,/# END DEV$$/d; /# BEGIN STAGE$$/,/# END STAGE$$/s/^(\s*)#/\1/g;' -i docker-compose.ci.yml
