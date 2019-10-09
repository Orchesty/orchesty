TEST=make test && make docker-down-clean
CLEAN=rm -rf vendor && make docker-up-force
COMPOSER=make composer-update
INSTALL1=docker-compose exec -T app composer global require hirak/prestissimo
INSTALL2=docker-compose exec -T php-dev composer global require hirak/prestissimo
INSTALL3=docker-compose exec -T pipes-api composer global require hirak/prestissimo

test: test-php

test-php:
	cd pipes-php-sdk && $(TEST)
	cd app-store && $(TEST)
	cd pipes-connectors && $(TEST)
	cd pf-bundles && $(TEST)
	cd portal && $(TEST)
	cd notification-sender && $(TEST)
	cd clients/demo/pipes-api && $(TEST)

vendor-refresh:
	cd pipes-php-sdk && $(CLEAN) && $(INSTALL2) && $(COMPOSER)
	cd app-store && $(CLEAN) && $(INSTALL1) && $(COMPOSER)
	cd pipes-connectors && $(CLEAN) && $(INSTALL1) && $(COMPOSER)
	cd pf-bundles && $(CLEAN) && $(INSTALL2) && $(COMPOSER)
	cd portal && $(CLEAN) && $(INSTALL1) && $(COMPOSER)
	cd notification-sender && $(CLEAN) && $(INSTALL1) && $(COMPOSER)
	cd clients/demo/pipes-api && $(CLEAN) && $(INSTALL3) && $(COMPOSER)
