.PHONY: docker-build docker-push go-test

DC=docker-compose
DE=docker-compose exec -T app

TAG := dev
DOCKER_REGISTRY := dkr.hanaboso.net/pipes/pipes/limiter
PUBLIC_REGISTRY := hanaboso/limiter

init: .env docker-up-force

.env:
	sed -e 's/{DEV_UID}/$(shell id -u)/g' \
		-e 's/{DEV_GID}/$(shell id -g)/g' \
		-e 's/{GITLAB_CI}/$(echo $GITLAB_CI)/g' \
		.env.dist >> .env; \

lint:
	$(DE) go fmt ./...
	excludes='';\
	for file in $$(ls -R $$(find . -type f ) | grep test.go); do\
		excludes="$${excludes} -exclude $$(echo $${file} | cut -c 3-)";\
	done;\
	$(DE) revive -config config.toml $${excludes} -formatter friendly ./...

build:
	docker build -t $(DOCKER_REGISTRY):$(TAG) .
	docker push $(DOCKER_REGISTRY):$(TAG)
	docker tag ${DOCKER_REGISTRY}:${TAG} $(PUBLIC_REGISTRY):$(TAG)
	docker push $(PUBLIC_REGISTRY):$(TAG)

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

test: docker-up-force fasttest docker-down-clean

ci-test: #test

fasttest:
	$(DE) mkdir var || true
	$(DE) go test -cover -coverprofile var/coverage.out ./... -count=1
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html

clean:
	docker-compose down -v
