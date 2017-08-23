.PHONY: docker-up docker-up-force composer-install composer-update codesniffer phpstan phpunit init test

DC=docker-compose
DE=docker-compose exec -T mapper

docker-up:
	$(DC) up -d

docker-up-force:
	$(DC) up -d --force-recreate

composer-install:
	$(DE) composer install --ignore-platform-reqs

composer-update:
	$(DE) composer update --ignore-platform-reqs

codesniffer:
	$(DE) php ./vendor/bin/phpcs --standard=./ruleset.xml --colors -p src/ tests/

phpstan:
	$(DE) php ./vendor/bin/phpstan analyse -l 7 src/
	$(DE) php ./vendor/bin/phpstan analyse -l 5 tests/

phpunit:
	$(DE) php ./vendor/bin/phpunit -c phpunit.xml.dist --dont-report-useless-tests --colors --stderr tests

init: docker-up composer-install

test: docker-up composer-install codesniffer phpstan phpunit
