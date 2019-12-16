.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/notification-sender
BASE=hanabosocom/php-dev:php-7.4

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

database-clear:
	$(DE) bin/console doctrine:mongodb:schema:drop || true

# App
init-dev: docker-up-force composer-install

create-index:
	$(DE) bin/console d:m:s:c --index

phpcodesniffer:
	$(DE) vendor/bin/phpcs -p --standard=ruleset.xml --colors src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c phpstan.neon -l 8 --memory-limit=512M src tests

phpintegration:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --colors tests/Integration

phpcontroller: database-clear
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 1 --colors tests/Controller

phpcoverage:
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p 4 --coverage-html var/coverage --whitelist src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh 60

test: docker-up-force composer-install fasttest docker-down-clean

fasttest: phpcodesniffer clear-cache phpstan phpintegration phpcontroller phpcoverage-ci
