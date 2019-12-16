.PHONY: docker-up-force docker-down-clean test

TAG?=php-7.4
GITLAB=dkr.hanaboso.net/pipes/pipes/php-dev:${TAG}
DOCKERHUB=hanabosocom/php-dev:${TAG}
BASE=hanabosocom/php-base:php-7.4-alpine
DC=docker-compose
DE=docker-compose exec -T php-dev
DEC=docker-compose exec -T php-dev composer

# Build
build-dev:
	docker pull ${BASE}
	cd docker/php-dev/ && docker build -t ${GITLAB} .
	cd docker/php-dev/ && docker build -t ${DOCKERHUB} .
	docker push ${GITLAB}
	docker push ${DOCKERHUB}

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans
	sleep 10

docker-down-clean: .env
	$(DC) down -v

#Composer
composer-install:
	$(DEC) install

composer-update:
	$(DEC) update

composer-outdated:
	$(DEC) outdated

# App
init: .env docker-up-force composer-install

#CI
codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c phpstan.neon -l 8 src/ tests/

phpunit:
	$(DE) ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --colors --stderr tests/Unit

phpcontroller:
	$(DE) ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --colors --stderr tests/Controller

phpintegration: database-create
	$(DE) ./vendor/bin/phpunit -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist --colors --stderr tests/Integration

phpcoverage:
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh 50

phpmanual-up:
	cd tests/Manual; $(MAKE) docker-up-force;

phpmanual-tests:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Manual/

phpmanual-down:
	cd tests/Manual; $(MAKE) docker-down-clean;

test: docker-up-force composer-install fasttest docker-down-clean

fasttest: codesniffer clear-cache phpstan phpunit phpintegration phpcontroller phpcoverage-ci

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#Other
console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php bin/console cache:clear --env=test
	$(DE) php bin/console cache:warmup --env=test

database-create:
	$(DE) php bin/console doctrine:mongodb:schema:create --dm=metrics || true

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \
