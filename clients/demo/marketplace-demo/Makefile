DCS=docker-compose exec -T app
IMAGE=hanaboso/demo-login

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

build:
	if ! docker buildx inspect multi; then docker buildx create --name multi --platform linux/amd64,linux/arm64/v8 --use --bootstrap; fi
	docker buildx build --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE):$(TAG) .

docker-up-force: .env
	docker-compose pull --ignore-pull-failures
	docker-compose up -d --force-recreate --remove-orphans --build

docker-down-clean: .env
	docker-compose down -v

start:
	$(DCS) pnpm run start

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
