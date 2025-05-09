DCS=docker-compose exec -T applinth-billing-processor
IMAGE=dkr.hanaboso.net/pipes/pipes/saas/applinth-billing-processor:$(TAG)

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

docker-up-force: .env .lo0-up
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans

docker-down-clean: .env .lo0-down
	docker-compose down -v

# Build
build: .env
	docker build -f Dockerfile -t $(IMAGE) --pull .
	docker push $(IMAGE)

build-dev:
	docker build ./docker -t node-sdk:tag

start:
	$(DCS) pnpm run start

init: docker-up-force install

install:
	$(DCS) pnpm install

update:
	$(DCS) pnpm update

lint:
	$(DCS) pnpm run lint

unit:
	$(DCS) pnpm run test

localtest:
	pnpm run lint
	pnpm run test

fasttest: lint unit

test: docker-up-force install fasttest docker-down-clean

ci-test: test

create-fixture-data:
	$(DCS) pnpm run create-fixture-data
