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
	$(DEC) install --ignore-platform-reqs -v

# App
init: .env docker-up-force composer-install

#Other

console:
	$(DE) php bin/console ${command}

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