.PHONY: docker docker-up test codesniffer phpstan phpunit
.DEFAULT_GOAL := docker

docker:
	docker-compose exec -T php-dev sh -c '${docker}'

docker-up: .env
	docker-compose pull
	docker-compose up -d

docker-up-force: .env
	docker-compose pull
	docker-compose up -d --force-recreate

docker-down-clean: .env
	docker-compose down -v

#Composer

composer:
	$(MAKE) docker docker="composer ${command}"

composer-install:
	$(MAKE) composer command="install --ignore-platform-reqs"

composer-update:
	$(MAKE) composer command="update --ignore-platform-reqs"

composer-outdated:
	$(MAKE) composer command=outdated

composer-require:
	$(MAKE) composer command="require ${package}"

composer-require-dev:
	$(MAKE) composer command="require --dev ${package}"

composer-deploy:
	$(MAKE) composer command="update --prefer-dist --no-dev -o"

#CI

codesniffer:
	$(MAKE) docker docker="./vendor/bin/phpcs --standard=./vendor/pipes/php-check-utils/ruleset.xml --colors -p src/ tests/"

phpstan:
	$(MAKE) docker docker="./vendor/bin/phpstan analyse -l 7 src/"
	$(MAKE) docker docker="./vendor/bin/phpstan analyse -l 5 tests/"

phpunit:
	$(MAKE) docker docker="./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests/Unit"

phpintergration:
	$(MAKE) docker docker="./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests/Integration/"

test: docker-up-force composer-install codesniffer phpstan clear-cache phpunit phpintergration

#Other

console:
	$(MAKE) docker docker="php bin/console ${command}"

clear-cache:
	$(MAKE) docker docker="sudo rm -rf app/cache"

.env: DEV_UID != id -u
.env: DEV_GID != id -g
.env:
	$(file >$@,DEV_UID=${DEV_UID})
	$(file >>$@,DEV_GID=${DEV_GID})
	$(file >>$@,DEV_IP=127.0.0.2)
