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
	$(MAKE) docker docker="./vendor/bin/phpcs --standard=ruleset.xml --colors -p src/ tests/"

phpstan:
	$(MAKE) docker docker="./vendor/bin/phpstan analyse -l 7 src/"
	$(MAKE) docker docker="./vendor/bin/phpstan analyse -l 5 tests/"

phpunit:
	$(MAKE) docker docker="./vendor/bin/phpunit --dont-report-useless-tests --colors tests/"

test: codesniffer phpstan phpunit

#Other

console:
	$(MAKE) docker docker="php bin/console ${command}"

.env: DEV_UID != id -u
.env: DEV_GID != id -g
.env:
	$(file >$@,DEV_UID=${DEV_UID})
	$(file >>$@,DEV_GID=${DEV_UID})
