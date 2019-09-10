.PHONY: docker-up docker-up-force docker-down-clean test codesniffer phpstan phpunit

TAG?=dev
IMAGE=dkr.hanaboso.net/pipes/pipes/php-sdk/php-dev:${TAG}
BASE=dkr.hanaboso.net/hanaboso/php-base:php-7.3
DC=docker-compose
DE=docker-compose exec -T php-dev
DEC=docker-compose exec -T php-dev composer

# Build
build-dev:
	docker pull ${BASE}
	cd docker/php-dev/ && docker build -t ${IMAGE} .
	docker push ${IMAGE}

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans
	sleep 10

docker-down-clean: .env
	$(DC) down -v

#Composer
composer-install:
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) global require hirak/prestissimo
	$(DEC) update --ignore-platform-reqs

composer-outdated:
	$(DEC) outdated

composer-deploy:
	$(DEC) update --prefer-dist --no-dev -o

# App
init: .env docker-up-force composer-install

#CI
codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c phpstan.neon -l 7 src/ tests/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Unit

phpcontroller:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Controller

phpintegration: database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Integration

phpmanual-up:
	cd tests/Manual; $(MAKE) docker-up-force;

phpmanual-tests:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Manual/

phpmanual-down:
	cd tests/Manual; $(MAKE) docker-down-clean;

test: docker-up-force composer-install fasttest

fasttest: codesniffer clear-cache phpstan phpunit phpintegration phpcontroller

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#Other
console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) sudo rm -rf var/cache
	$(DE) php bin/console cache:warmup --env=test

database-create:
	sleep 5
	$(DE) php bin/console doctrine:database:drop --force || true
	$(DE) php bin/console doctrine:database:create
	$(DE) php bin/console doctrine:schema:create

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		.env.dist >> .env; \
