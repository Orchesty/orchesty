BASE_IMAGE=dkr.hanaboso.net/pipes/pipes/nodejs-build
PUBLIC_BASE_IMAGE=hanabosocom/nodejs-build

IMAGE?=dkr.hanaboso.net/pipes/pipes/applinth-admin-ui
PUBLIC_IMAGE?=hanaboso/pipes-applinth-admin-ui

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
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

init: .env .lo0-up
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans

start-mock: init
	docker-compose exec applinth-admin-ui pnpm run api:mock

down: .lo0-down
	docker-compose down -v

rebuild:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)

build-dev:
	cd docker && docker build -t ${BASE_IMAGE}:${TAG} --pull .
	docker push ${BASE_IMAGE}:${TAG}
	docker tag ${BASE_IMAGE}:${TAG} $(PUBLIC_BASE_IMAGE):$(TAG)
	docker push $(PUBLIC_BASE_IMAGE):$(TAG)

generate-fixture-data: init
	docker-compose exec console-api pnpm run create-fixture-data

