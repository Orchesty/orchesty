.PHONY: init-dev init-prod generate build-monolith-fpm push-monolith-fpm

# Client-specific vars

CLIENT_DIR="clients/clever-connectors"

TAG?=dev
TAG_MONOLITH_FPM?=$(TAG)
IMAGE_MONOLITH_FPM=dkr.hanaboso.net/pipes/client-cm/monolith-fpm:$(TAG_MONOLITH_FPM)


# Generic vars

IMAGE_PHP_DEV=dkr.hanaboso.net/pipes/pipes/php-dev:dev

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		-e "s|{DOCKER_GID}|$(shell getent group docker /etc/group | awk -F ':' '{print $$3}')|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		.env.dist >> .env; \

docker-pull-dev:
	$(DC_DEV) pull

up: .env
	$(DC_DEV) pull
	$(DC_DEV) up -d --force-recreate --remove-orphans

init-dev: .env
	$(DC_DEV) pull
	rm -rf pipes-api/var/cache/*
	$(DC_DEV) up -d --force-recreate --remove-orphans
#	$(DE_DEV) monolith-fpm composer global require hirak/prestissimo
	$(DE_DEV) monolith-fpm composer install --ignore-platform-reqs
	$(DE_DEV) api-demo-fpm composer install

destroy-dev:
	$(DC_DEV) down -v

composer-update-dev:
	$(DE_DEV) monolith-fpm composer global require hirak/prestissimo
	$(DE_DEV) monolith-fpm composer update --ignore-platform-reqs

build-monolith-fpm:
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
		-v ${HOME}/.composer:/srv/.composer \
		-w /build/${CLIENT_DIR}/pipes-api \
		-e COMPOSER=composer.prod.json \
		-e SYMFONY_ENV=prod \
		${IMAGE_PHP_DEV} \
		composer install --no-dev --optimize-autoloader && \
	: Build an app docker image from the artifacts && \
	docker build -f docker/Dockerfile.monolith-fpm -t ${IMAGE_MONOLITH_FPM} .

push-monolith-fpm:
	docker push $(IMAGE_MONOLITH_FPM)

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml
	# Comment/uncomment DEV/STAGE sections
	sed -r '/# BEGIN DEV$$/,/# END DEV$$/d; /# BEGIN STAGE$$/,/# END STAGE$$/s/^(\s+)#/\1/g;' -i docker-compose.ci.yml

generate:
	curl -X POST -H content-type=application/json http://127.0.0.66/api/gateway/test/topology/generate

cache-clear:
	$(DE_DEV) monolith-fpm bin/console cache:clear
