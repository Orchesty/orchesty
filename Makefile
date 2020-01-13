TEST=make test && make docker-down-clean
VENDOR=rm -rf vendor
DOCKER=make docker-up-force
INSTALL=docker-compose exec -T app composer global require hirak/prestissimo
COMPOSER=make composer-update

test: test-php

test-php:
	cd pipes-php-sdk && $(TEST)
	cd app-store && $(TEST)
	cd pipes-connectors && $(TEST)
	cd pf-bundles && $(TEST)
	cd portal && $(TEST)
	cd notification-sender && $(TEST)
	cd clients/demo/pipes-api && $(TEST)

vendor-remove:
	cd pipes-php-sdk && $(VENDOR)
	cd app-store && $(VENDOR)
	cd pipes-connectors && $(VENDOR)
	cd pf-bundles && $(VENDOR)
	cd portal && $(VENDOR)
	cd notification-sender && $(VENDOR)
	cd clients/demo/pipes-api && $(VENDOR)

vendor-refresh:
	cd pipes-php-sdk && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd app-store && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd pipes-connectors && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd pf-bundles && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd portal && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd notification-sender && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd clients/demo/pipes-api && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
