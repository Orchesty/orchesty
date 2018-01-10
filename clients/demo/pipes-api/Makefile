.PHONY: docker-up docker-up-force .env composer-install composer-update codesniffer phpstan phpunit init test

DC=docker-compose
DE=docker-compose exec -T pipes-api
DEC=docker-compose exec -T pipes-api composer

.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

docker-up: .env
	$(DC) up -d

docker-up-force: .env
	$(DC) up -d --force-recreate --remove-orphans

composer-install:
	$(DEC) global require hirak/prestissimo
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) global require hirak/prestissimo
	$(DEC) update --ignore-platform-reqs

codesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -l 7 src/
	$(DE) php ./vendor/bin/phpstan analyse -l 5 tests/

phpunit:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests

init: docker-up composer-install

test: docker-up-force composer-install codesniffer phpstan phpunit
