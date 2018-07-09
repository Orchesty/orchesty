.PHONY: docker-up .env docker-up-force docker-down-clean test codesniffer phpstan phpunit

DC=docker-compose
DE=docker-compose exec -T cm-api
DEC=docker-compose exec -T cm-api composer

# Docker
docker-up: .env
	$(DC) pull
	$(DC) up -d

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

#Composer

composer-install:
#	$(DEC) global require hirak/prestissimo
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

composer-clear-cache:
	$(DEC) clear-cache

composer-deploy:
	$(DEC) update --prefer-dist --no-dev -o

# App
init: .env docker-up-force composer-install

#CI

codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan analyse --memory-limit=300M -c phpstan.neon -l 4 src/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Unit

phpintergration: #database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Integration

phplive: #database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Live

phpcontroller: #database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Controller

test: docker-up-force composer-install codesniffer phpstan phpunit phpintergration phpcontroller

fasttest: codesniffer phpstan phpunit phpintergration phpcontroller

#Other

console:
	$(DE) php bin/console ${command}

create-schema:
	$(DE) bin/console doctrine:mongodb:schema:create

database-create:
	$(DE) php bin/console doctrine:database:drop --force || true
	$(DE) php bin/console doctrine:database:create
	$(DE) php bin/console doctrine:schema:create
	$(DE) php bin/console doctrine:fixtures:load --append

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml