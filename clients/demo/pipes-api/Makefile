.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T app
DEC=docker-compose exec -T app composer

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g" \
		-e "s/{DEV_GID}/$(shell id -u)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo "\/tmp\/.ssh-auth-sock"; else echo '\/tmp\/.nope'; fi)/g" \
		.env.dist >> .env; \

docker-down-clean: .env
	$(DC) down -v

docker-up-force: .env
	$(DC) up -d --force-recreate --remove-orphans

composer-install:
	$(DEC) install --no-suggest

composer-update:
	$(DEC) update --no-suggest

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php bin/console cache:clear --env=test
	$(DE) php bin/console cache:warmup --env=test

codesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./ruleset.xml src/ tests/

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 8 src/ tests/

phpcontroller:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors tests/Controller

phpintegration:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors tests/Integration

phplive:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors tests/Live

init: docker-up-force composer-install

test: init fasttest docker-down-clean

fasttest: clear-cache codesniffer phpstan phpintegration phpcontroller

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
