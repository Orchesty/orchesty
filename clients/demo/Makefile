.PHONY: init-dev init-prod generate build-monolith push-monolith

# Client-specific vars

CLIENT_DIR="clients/demo"

TAG?=dev
TAG_MONOLITH_FPM?=$(TAG)
IMAGE_MONOLITH_FPM=dkr.hanaboso.net/pipes/demo/monolith:$(TAG_MONOLITH_FPM)
TAG_MONOLITH?=$(TAG)
IMAGE_MONOLITH=dkr.hanaboso.net/pipes/demo/monolith:$(TAG_MONOLITH_FPM)
TAG_SENDER_API?=master
IMAGE_SENDER_API=dkr.hanaboso.net/pipes/notification-sender:$(TAG_SENDER_API)

# Generic vars

IMAGE_PHP_DEV=hanabosocom/php-dev:php-7.3

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
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
	$(DE_DEV) monolith-api composer install --ignore-platform-reqs
	$(DE_DEV) monolith-api rm -rf var/cache/*

destroy-dev:
	$(DC_DEV) down -v

composer-update-dev:
	$(DE_DEV) monolith-api composer global require hirak/prestissimo
	$(DE_DEV) monolith-api composer update --ignore-platform-reqs

build-monolith: .env
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
		-v $${HOME}/dev/.composer:$${HOME}/dev/.composer \
		-w /build/${CLIENT_DIR}/pipes-api \
		-e COMPOSER=composer.prod.json \
		-e APP_ENV=prod \
		-e APP_DEBUG=0 \
		-e COMPOSER_HOME=$${HOME}/dev/.composer \
		${IMAGE_PHP_DEV} \
		bash -c 'composer install --no-dev --optimize-autoloader --ignore-platform-reqs && bin/console cache:warmup' && \
	: Build an app docker image from the artifacts && \
	docker build -f docker/Dockerfile.monolith -t ${IMAGE_MONOLITH} .

build-sender:  .env
	cd sender-api && \
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
		-v $${HOME}/dev/.composer:${HOME}/dev/.composer \
		-w /build/${CLIENT_DIR}/sender-api \
		-e COMPOSER=composer.prod.json \
		-e APP_ENV=prod \
		-e APP_DEBUG=0 \
		${IMAGE_PHP_DEV} \
		bash -c 'composer install --no-dev --optimize-autoloader --ignore-platform-reqs && rm -rf var && bin/console cache:warmup' && \
	: Build an app docker image from the artifacts && \
	docker build -f docker/Dockerfile.sender-api -t ${IMAGE_SENDER_API} .

push-monolith:
	docker push $(IMAGE_MONOLITH)

push-sender:
	docker push $(IMAGE_SENDER_API)

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml
	# Comment/uncomment DEV/STAGE sections
	sed -r '/# BEGIN DEV$$/,/# END DEV$$/d; /# BEGIN STAGE$$/,/# END STAGE$$/s/^(\s*)#/\1/g;' -i docker-compose.ci.yml

cache-clear:
	$(DE_DEV) monolith-api bin/console cache:clear
