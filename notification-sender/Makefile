.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/notification-sender
PHP_DEV=hanabosocom/php-base:php-7.4-alpine

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \

prod-build: .env
	docker pull $(IMAGE):dev
	docker-compose -f docker-compose.yml run --rm --no-deps app  composer install --ignore-platform-reqs
	docker build -t $(IMAGE):master .
	docker push $(IMAGE):master

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
build: .env docker-compose.ci.yml
	docker pull $(PHP_DEV)
	docker-compose -f docker-compose.ci.yml run --rm --no-deps app \
		bash -c "composer install --ignore-platform-reqs --no-dev && rm -rf ./var/* && php bin/console cache:warmup --env=prod"
	docker build -t $(IMAGE):${TAG} .
	docker push $(IMAGE):${TAG}

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install --no-suggest

composer-update:
	$(DE) composer update --no-suggest

clear-cache:
	$(DE) rm -rf var/log
	$(DE) bin/console cache:clear --env=test
	$(DE) bin/console cache:warmup --env=test

database-clear:
	$(DE) bin/console doctrine:mongodb:schema:drop || true

# App
init-dev: docker-up-force composer-install

create-index:
	$(DE) bin/console d:m:s:c --index

phpcodesniffer:
	$(DE) vendor/bin/phpcs --standard=tests/ruleset.xml src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpunit:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Unit

phpintegration:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Integration

phpcontroller: database-clear
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Controller

phpcoverage: database-clear
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh

test: docker-up-force composer-install fasttest docker-down-clean

fasttest: clear-cache phpcodesniffer phpstan wait-for-server-start phpunit phpintegration phpcontroller phpcoverage-ci

wait-for-server-start:
	$(DE) /bin/bash -c 'while [ $$(curl -s -o /dev/null -w "%{http_code}" http://guest:guest@rabbitmq:15672/api/overview) == 000 ]; do sleep 1; done'
