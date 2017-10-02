.PHONY: docker-up .env docker-up-force docker-down-clean test codesniffer phpstan phpunit

DC=docker-compose
DE=docker-compose exec -T php-dev
DEC=docker-compose exec -T php-dev composer

# Docker
docker-up: .env
	$(DC) pull
	$(DC) up -d

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate

docker-down-clean: .env
	$(DC) down -v

#Composer

composer-install:
	$(DEC) global require hirak/prestissimo
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) global require hirak/prestissimo
	$(DEC) update --ignore-platform-reqs

composer-outdated:
	$(DEC) outdated

composer-require:
	$(DEC) require ${package}

composer-require-dev:
	$(DEC) require --dev ${package}

composer-deploy:
	$(DEC) update --prefer-dist --no-dev -o

# App
init: .env docker-up-force composer-install

#CI

codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan --memory-limit=150M analyse -c ./vendor/pipes/php-check-utils/phpstan.neon -l 7 src/
	$(DE) ./vendor/bin/phpstan --memory-limit=150M analyse -c ./vendor/pipes/php-check-utils/phpstan.neon -l 4 tests/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Unit

phpcontroller:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Controller

phpintegration: database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Integration/

phpmanual-up:
	cd tests/Manual; $(MAKE) docker-up-force;

phpmanual-tests:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Manual/

phpmanual-down:
	cd tests/Manual; $(MAKE) docker-down-clean;

test: docker-up-force composer-install codesniffer phpstan clear-cache phpunit phpcontroller phpintegration

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

#Other

console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) sudo rm -rf app/cache

database-create:
	$(DE) php bin/console doctrine:database:drop --force || true
	$(DE) php bin/console doctrine:database:create
	$(DE) php bin/console doctrine:schema:create

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;
