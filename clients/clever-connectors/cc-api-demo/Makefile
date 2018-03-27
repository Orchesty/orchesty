.PHONY: init composer-update

DC=docker-compose
DE=docker-compose exec app
DP=docker-compose exec app bin/console
DS=docker-compose exec mariadb
IMAGE_API_DEMO_FPM=dkr.hanaboso.net/pipes/client-cm/api-demo:latest

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g; s/{DEV_GID}/$(shell id -u)/g; s/{DEV_USERNAME}/$(shell id -un)/g;" .env.dist > .env

init: .env
	$(DC) up -d --force-recreate --remove-orphans
	$(DE) composer install

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

clear-cache-orm:
	$(DP) orm:clear-cache:metadata
	$(DP) orm:clear-cache:query
	$(DP) orm:clear-cache:result

database-create: clear-cache-orm
	$(DS) /bin/sh -c 'mysql -uroot -proot -e "DROP DATABASE IF EXISTS \`cm-apidemo\`; CREATE DATABASE \`cm-apidemo\`; USE \`cm-apidemo\`;"'
	$(DP) orm:schema-tool:create

build: .env
	: Disable dependency symlinking in composer and do a fresh install && \
	rm -rf vendor && \
	for EXT in json lock; do \
		sed -r 's/("symlink": )true/\1false/g' composer.$${EXT} > composer.prod.$${EXT}; \
	done && \
	: Build the app && \
	docker-compose run --rm -e COMPOSER=composer.prod.json app composer install --optimize-autoloader && \
	: Build an app docker image from the artifacts && \
	docker build -t ${IMAGE_API_DEMO_FPM} .

push:
	docker push $(IMAGE_API_DEMO_FPM)

composer-install:
	$(DE) composer install --ignore-platform-reqs

composer-update:
	$(DE) composer update --ignore-platform-reqs


codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p socialMultichannel/

phpstan:
	$(DE) ./vendor/bin/phpstan --memory-limit=200M analyse -c phpstan.neon -l 4 socialMultichannel/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr socialMultichannel/tests/Unit

phpintegration: database-create
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr socialMultichannel/tests/Integration

test: docker-up-force composer-install codesniffer phpstan phpunit phpintegration

fasttest: codesniffer phpstan phpunit phpintegration