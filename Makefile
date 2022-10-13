TEST=make test && make docker-down-clean
VENDOR=rm -rf vendor || true
VAR=rm -rf var || true
DOCKER=make docker-up-force
INSTALL=docker-compose exec -T app composer global require hirak/prestissimo
COMPOSER=make composer-update

test: test-php test-go

test-go:
	cd starting-point && $(TEST)
	cd rabbitmq-telegraf && $(TEST)
	cd topology-generator && $(TEST)

test-php:
	cd pipes-php-sdk && $(TEST)
	cd pipes-connectors && $(TEST)
	cd pf-bundles && $(TEST)
	cd applinth && $(TEST)
	cd clients/demo/pipes-api && $(TEST)

vendor-remove: var-remove
	cd pipes-php-sdk && $(VENDOR)
	cd pipes-connectors && $(VENDOR)
	cd pf-bundles && $(VENDOR)
	cd applinth && $(VENDOR)
	cd clients/demo/pipes-api && $(VENDOR)

var-remove:
	cd pipes-php-sdk && $(VAR)
	cd pipes-connectors && $(VAR)
	cd pf-bundles && $(VAR)
	cd applinth && $(VAR)
	cd clients/demo/pipes-api && $(VAR)

vendor-refresh:
	cd pipes-php-sdk && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd pipes-connectors && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd pf-bundles && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd applinth && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)
	cd clients/demo/pipes-api && $(VENDOR) && $(DOCKER) && $(INSTALL) && $(COMPOSER)

rebuild-all:
	cd detector && make build TAG=$(TAG)
	cd topology-generator && make build TAG=$(TAG)
	cd starting-point && make build TAG=$(TAG)
	cd pf-bundles && make build TAG=$(TAG)
	cd logstash && make docker-build docker-push TAG=$(TAG)
	cd kapacitor && make build TAG=$(TAG)
	cd frontend && make build-dev TAG=$(TAG)
	cd cron && make build TAG=$(TAG)
	cd counter && make build TAG=$(TAG)
	cd app-ui && make rebuild TAG=$(TAG)
	cd clients/demo/node-sdk && make build TAG=$(TAG)
	cd applinth && make build TAG=$(TAG)
	cd status-service && make build TAG=$(TAG)
