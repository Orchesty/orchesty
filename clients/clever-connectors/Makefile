.PHONY: init-dev init-prod generate build-monolith push-monolith

# Client-specific vars

CLIENT_DIR="clients/clever-connectors"

TAG?=dev
TAG_MONOLITH_FPM?=$(TAG)
IMAGE_MONOLITH_FPM=dkr.hanaboso.net/pipes/client-cm/monolith:$(TAG_MONOLITH_FPM)
TAG_MONOLITH?=$(TAG)
IMAGE_MONOLITH=dkr.hanaboso.net/pipes/client-cm/monolith:$(TAG_MONOLITH_FPM)


# Generic vars

IMAGE_PHP_DEV=dkr.hanaboso.net/pipes/pipes/php-dev:dev

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
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
#	$(DE_DEV) monolith-api composer global require hirak/prestissimo
	$(DE_DEV) monolith-api composer install --ignore-platform-reqs

destroy-dev:
	$(DC_DEV) down -v

composer-update-dev:
	$(DE_DEV) monolith-api composer global require hirak/prestissimo
	$(DE_DEV) monolith-api composer update --ignore-platform-reqs

build-monolith:
	cd pipes-api && \
	: Disable dependency symlinking in composer and do a fresh install && \
	rm -rf vendor && \
	for EXT in json lock; do \
		sed -r 's/("symlink": )true/\1false/g' composer.$${EXT} > composer.prod.$${EXT}; \
	done && \
	: Build the app && \
	docker pull ${IMAGE_PHP_DEV} && \
	docker run \
		--rm \
		-u $$(id -u):$$(id -g) \
		-v $$(pwd)/../../../:/build \
		-v $${SSH_AUTH_SOCK}:/tmp/bound-ssh-agent \
		-v $${HOME}/.composer:/srv/.composer \
		-w /build/${CLIENT_DIR}/pipes-api \
		-e COMPOSER=composer.prod.json \
		-e APP_ENV=prod \
		-e APP_DEBUG=0 \
		${IMAGE_PHP_DEV} \
		bash -c 'composer install --no-dev --optimize-autoloader && bin/console cache:warmup' && \
	: Build an app docker image from the artifacts && \
	docker build -f docker/Dockerfile.monolith -t ${IMAGE_MONOLITH} .

push-monolith:
	docker push $(IMAGE_MONOLITH)

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml
	# Comment/uncomment DEV/STAGE sections
	sed -r '/# BEGIN DEV$$/,/# END DEV$$/d; /# BEGIN STAGE$$/,/# END STAGE$$/s/^(\s*)#/\1/g;' -i docker-compose.ci.yml

cache-clear:
	$(DE_DEV) monolith-api bin/console cache:clear
