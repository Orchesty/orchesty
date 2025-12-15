TEST=make test
DOWN=make docker-down-clean
VENDOR=rm -rf vendor || true
VAR=rm -rf var || true
DOCKER=make docker-up-force
COMPOSER=make composer-update

test: test-php test-go test-js

test-go:
	cd bridge && $(TEST) && $(DOWN)
	cd counter && $(TEST) && $(DOWN)
	cd cron && $(TEST) && $(DOWN)
	cd detector && $(TEST) && $(DOWN)
	cd limiter && $(TEST) && $(DOWN)
	cd starting-point && $(TEST) && $(DOWN)
	cd topology-generator && $(TEST) && $(DOWN)

test-php:
	cd pf-bundles && $(TEST) && $(DOWN)
	cd applinth && $(TEST) && $(DOWN)
	cd clients/demo/pipes-api && $(TEST) && $(DOWN)

test-js:
	cd app-ui && $(TEST)
	cd applinth-admin-ui && $(TEST)
	cd applinth-marketplace-ui && $(TEST)
	cd clients/demo/node-sdk && $(TEST) && $(DOWN)
	cd saas/applinth-billing-processor && $(TEST) && $(DOWN)
	cd saas/console-api && $(TEST) && $(DOWN)
	cd saas/usccp && $(TEST) && $(DOWN)
	cd worker-api && $(TEST) && $(DOWN)

vendor-remove: var-remove
	cd pf-bundles && $(VENDOR)
	cd applinth && $(VENDOR)
	cd clients/demo/pipes-api && $(VENDOR)

var-remove:
	cd pf-bundles && $(VAR)
	cd applinth && $(VAR)
	cd clients/demo/pipes-api && $(VAR)

vendor-refresh:
	cd pf-bundles && $(VENDOR) && $(DOCKER) && $(COMPOSER)
	cd applinth && $(VENDOR) && $(DOCKER) && $(COMPOSER)
	cd clients/demo/pipes-api && $(VENDOR) && $(DOCKER) && $(COMPOSER)

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
	cd worker-api && make build TAG=$(TAG)
