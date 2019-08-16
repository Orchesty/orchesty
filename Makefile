test: test-php

test-php:
	cd app-store && make test && make docker-down-clean
	cd pf-bundles && make test && make docker-down-clean
	cd pipes-php-sdk && make test && make docker-down-clean
	cd pipes-connectors && make test && make docker-down-clean
	cd clients/demo/pipes-api && make test && make docker-down-clean
