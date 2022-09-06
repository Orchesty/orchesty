DC=docker-compose
DA=docker-compose exec -T app

IMAGE=dkr.hanaboso.net/pipes/pipes/status-service:$(TAG)
PUBLIC_IMAGE=hanaboso/status-service:$(TAG)

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \

init-dev: docker-up-force composer-install

# Docker section
docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

# Build
build: .env
	cp .dockerignore ../.dockerignore
	docker build -f Dockerfile -t $(IMAGE) --pull ../. || rm ../.dockerignore
	docker push $(IMAGE)
	docker tag ${IMAGE} $(PUBLIC_IMAGE)
	docker push $(PUBLIC_IMAGE)
	rm ../.dockerignore || true

# Composer section
composer-install:
	$(DA) composer instal

composer-update:
	$(DA) composer update
	$(DA) composer normalize

composer-outdated:
	$(DA) composer outdated

# App section
clear-cache:
	$(DA) rm -rf var/log
	$(DA) rm -rf var/coverage
	$(DA) php bin/console cache:clear --env=test
	$(DA) php bin/console cache:warmup --env=test

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#CI
codesniffer:
	$(DA) ./vendor/bin/phpcs --parallel=$$(nproc) --standard=tests/ruleset.xml src tests

phpstan:
	$(DA) ./vendor/bin/phpstan analyse -c tests/phpstan.neon -l 8 src tests

phpintegration:
	$(DA) vendor/bin/paratest -c ./vendor/hanaboso/php-check-utils/phpunit.xml.dist -p $$(nproc) --colors=always tests/Integration

ci-test: test

test: docker-up-force composer-install fasttest docker-down-clean

fasttest: clear-cache codesniffer phpstan phpintegration
