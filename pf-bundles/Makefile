IMAGE=orchesty/backend:$(TAG)

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env.dist | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \

# Build
build: .env
	cp .dockerignore ../.dockerignore
	docker build -f Dockerfile -t $(IMAGE) --pull ../. || rm ../.dockerignore
	docker push $(IMAGE)
	rm ../.dockerignore || true

# Docker
docker-up-force: .env .lo0-up
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env .lo0-down
	$(DC) down -v

#Composer
composer-install:
	$(DEC) install

composer-update:
	$(DEC) update
	$(DEC) update --dry-run roave/security-advisories
	$(DEC) normalize

composer-outdated:
	$(DEC) outdated

# App
init: .env docker-up-force composer-install

#CI
codesniffer:
	$(DE) ./vendor/bin/phpcs --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

codesnifferfix:
	$(DE) ./vendor/bin/phpcbf --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpunit:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Unit

phpintegration: database-create
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Integration

phpcontroller:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Controller/HbPFConfiguratorBundle/Controller

phpcoverage:
	$(DE) ./vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --coverage-html var/coverage --coverage-filter src tests

phpcoverage-ci:
	$(DE) ./vendor/hanaboso/php-check-utils/bin/coverage.sh -c 85 -p $$(nproc)

ci-test: test

test: docker-up-force composer-install fasttest docker-down-clean

fasttest: codesniffer clear-cache phpstan phpunit phpintegration phpcontroller phpcoverage-ci

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#Other
console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) rm -rf var
	$(DE) php bin/console cache:warmup --env=test

database-create:
	$(DE) php bin/console doctrine:mongodb:schema:update --dm default || true
	$(DE) php bin/console doctrine:mongodb:schema:update --dm metrics || true
	$(DE) php bin/console mongodb:index:update || true
