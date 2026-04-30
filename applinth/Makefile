DC=docker compose
DE=docker compose exec -T app
IMAGE=dkr.hanaboso.net/pipes/pipes/applinth:$(TAG)

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \


# Build
build:
	cp .dockerignore ../.dockerignore
	docker buildx build -f Dockerfile --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) ../. || rm ../.dockerignore
	rm ../.dockerignore || true

docker-compose.ci.yml:
	# Comment out any port
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

# Docker
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Composer
composer-install:
	$(DE) composer install

composer-update:
	$(DE) composer update
	$(DE) composer update --dry-run roave/security-advisories
	$(DE) composer normalize

composer-outdated:
	$(DE) composer outdated

clear-cache:
	$(DE) rm -rf var
	$(DE) bin/console cache:warmup --env=test

# App
init-dev: docker-up-force composer-install

create-index:
	$(DE) bin/console d:m:s:c --index

phpcodesniffer:
	$(DE) vendor/bin/phpcs --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

codesnifferfix:
	$(DE) ./vendor/bin/phpcbf --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

phpstan:
	$(DE) vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpintegration: database-create
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Integration

phpcontroller:
	$(DE) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Controller

phpcoverage:
	$(DE) php vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --coverage-html var/coverage --cache-directory var/cache/coverage --coverage-filter src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -p $$(nproc) -c 70

database-create:
	$(DE) php bin/console doctrine:mongodb:schema:update --dm default || true
	$(DE) php bin/console doctrine:mongodb:schema:update --dm metrics || true
	$(DE) php bin/console mongodb:index:update || true

test: docker-up-force composer-install fasttest docker-down-clean

ci-test: test

fasttest: phpcodesniffer clear-cache phpstan phpintegration phpcontroller phpcoverage-ci
