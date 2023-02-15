DC=docker-compose exec -T app

ALIAS?=alias
Darwin:
	sudo ifconfig lo0 $(ALIAS) $(shell awk '$$1 ~ /^DEV_IP/' .env.dist | sed -e "s/^DEV_IP=//")
Linux:
	@echo 'skipping ...'
.lo0-up:
	-@make `uname`
.lo0-down:
	-@make `uname` ALIAS='-alias'
.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e 's/{ORCHESTY_API_KEY}/$(shell tr -dc A-Za-z0-9 </dev/urandom | head -c 20)/g' \
		.env.dist > .env

build: .env
	docker build -f docker/Dockerfile -t ${IMAGE} --pull
	docker push $(IMAGE)

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml

init: .env .lo0-up
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans --build

docker-down-clean: .env .lo0-down
	docker-compose down -v

lint:
	$(DC) pnpm run lint-ci

unit:
	$(DC) pnpm run test

fasttest: lint unit

test: init fasttest docker-down-clean
