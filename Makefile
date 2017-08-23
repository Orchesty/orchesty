.PHONY: docker-up docker-up-force docker-down-clean test codesniffer phpstan phpunit

DC=docker-compose
DE=docker-compose exec -T php-dev
DEC=docker-compose exec -T php-dev composer

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
	$(DEC) install --ignore-platform-reqs

composer-update:
	$(DEC) update --ignore-platform-reqs

composer-outdated:
	$(DEC) outdated

composer-require:
	$(DEC) require ${package}

composer-require-dev:
	$(DEC) require --dev ${package}

composer-deploy:
	$(DEC) update --prefer-dist --no-dev -o

#CI

codesniffer:
	$(DE) ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) ./vendor/bin/phpstan analyse -l 7 src/
	$(DE) ./vendor/bin/phpstan analyse -l 5 tests/

phpunit:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests/Unit

phpintergration:
	$(DE) ./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests/Integration/

test: docker-up-force composer-install codesniffer phpstan clear-cache phpunit phpintergration

#Other

console:
	$(DE) php bin/console ${command}

clear-cache:
	$(DE) sudo rm -rf app/cache

.env: DEV_UID != id -u
.env: DEV_GID != id -g
.env:
	$(file >$@,DEV_UID=${DEV_UID})
	$(file >>$@,DEV_GID=${DEV_GID})
	$(file >>$@,DEV_IP=127.0.0.2)
