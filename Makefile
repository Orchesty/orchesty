test: test-php

test-php:
	cd pipes-php-sdk && make test && make docker-down-clean
	cd pipes-connectors && make test && make docker-down-clean
	cd app-store && make test && make docker-down-clean
	cd pf-bundles && make test && make docker-down-clean
	cd notification-sender && make test && make docker-down-clean
	cd clients/demo/pipes-api && make test && make docker-down-clean
	cd clients/demo/sender-api && make test FILE=docker-compose.yml && make docker-down-clean FILE=docker-compose.yml
