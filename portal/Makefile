.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/portal
BASE=hanabosocom/php-dev:php-7.3

SLS_ENV?=

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \

build: .env
	docker pull $(IMAGE):dev
	docker-compose -f docker-compose.yml run --rm --no-deps app  composer install --ignore-platform-reqs
	docker build -t $(IMAGE):master .
	docker push $(IMAGE):master

build-sls: .env
	$(DC) run --rm --no-deps app composer install --prefer-dist --optimize-autoloader --no-dev --ignore-platform-reqs
	$(DC) run --rm --no-deps -e APP_ENV=serverless -e APP_DEBUG=0 app bin/console cache:warmup

deploy-sls:
ifndef SLS_ENV
	$(error SLS_ENV must be specified)
endif
	# Can't echo this command, because it would reweal AWS key
	@docker run \
	  --rm \
	  -e AWS_ACCESS_KEY_ID=${AWS_ACCESS_KEY_ID} \
	  -e AWS_SECRET_ACCESS_KEY=${AWS_SECRET_ACCESS_KEY} \
	  -e HOME=/tmp \
	  -u $(shell id -u):$(shell id -g) \
	  -v $(shell pwd):/app \
	  -w /app \
	  fjferrer/serverless-docker:3.0.1 \
	  sls deploy --stage ${SLS_ENV}

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --ignore-platform-reqs

composer-update:
	$(DE) composer update --ignore-platform-reqs

clear-cache:
	$(DE) rm -rf var/log
	$(DE) bin/console cache:clear --env=test
	$(DE) bin/console cache:warmup --env=test

# App
init-dev: docker-up-force composer-install

create-index:
	$(DE) bin/console d:m:s:c --index

phpcodesniffer:
	$(DE) vendor/bin/phpcs -p --standard=ruleset.xml --colors src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c phpstan.neon -l 7 --memory-limit=512M src tests

phpintegration:
	$(DE) vendor/bin/paratest -c phpunit.xml.dist -p 4 --colors tests/Integration

phpcontroller:
	$(DE) vendor/bin/paratest -c phpunit.xml.dist -p 4 --colors tests/Controller

test: docker-up-force composer-install fasttest

fasttest: phpcodesniffer clear-cache phpstan phpintegration phpcontroller
