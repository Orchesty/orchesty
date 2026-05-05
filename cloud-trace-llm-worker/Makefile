DCS=docker-compose exec -T sdk
IMAGE=dkr.hanaboso.net/pipes/pipes/cloud-trace-llm-worker:$(TAG)

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist > .env; \

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yaml > docker-compose.ci.yml

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

typecheck:
	$(DCS) pnpm run typecheck

unit:
	$(DCS) pnpm run test

localtest:
	pnpm run lint
	pnpm run typecheck
	pnpm run test

fasttest: lint typecheck unit

test: docker-up-force install fasttest docker-down-clean

ci-test: test
