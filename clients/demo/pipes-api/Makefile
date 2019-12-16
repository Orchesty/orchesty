.PHONY: docker-up-force docker-down-clean test

DC=docker-compose
DE=docker-compose exec -T pipes-api
DEC=docker-compose exec -T pipes-api composer

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
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) update --ignore-platform-reqs

clear-cache:
	$(DE) rm -rf var/log
	$(DE) php bin/console cache:clear --env=test
	$(DE) php bin/console cache:warmup --env=test

codesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -c ./phpstan.neon -l 8 src/ tests/

phpcontroller:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Controller

phpintegration:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Integration

phplive:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --colors --stderr tests/Live

init: docker-up-force composer-install

test: init fasttest

fasttest: clear-cache codesniffer phpstan phpintegration phpcontroller

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml
