DC=docker compose
DE=docker compose exec -T app
DB=docker compose exec -T bruno
DM=docker compose exec -T mongo
IMAGE=dkr.hanaboso.net/pipes/pipes/trace:$(TAG)

.env:
	sed -e 's/{DEV_UID}/$(shell id -u)/g' \
		-e 's/{DEV_GID}/$(shell id -g)/g' \
		.env.dist >> .env; \

build:
	docker buildx build --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) .

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

go-update:
	$(DE) su-exec root go get -u all
	$(DE) su-exec root go mod tidy
	$(DE) su-exec root chown dev:dev go.mod go.sum

init-dev: docker-up-force wait-for-server-start
	$(DM) mongosh --quiet --eval 'db.getSiblingDB("trace").User.insertOne({ created: new Date(), email :"test@orchesty.io", password: "$$2y$$12$$FwDiQC043aalpovhZWqn3.5VvHc5yGWcvcnbSNmo9stx./sr7bDti", deleted: false })'

wait-for-server-start:
	$(DE) /bin/sh -c 'while [ $$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8080/status) != 200 ]; do sleep 1; done'

lint:
	$(DE) go fmt ./...
	excludes='';\
	for file in $$(find . -type f -name '*_test.go'); do\
		excludes="$${excludes} -exclude $$(echo $${file} | cut -c 3-)";\
	done;\
	$(DE) revive -config config.toml $${excludes} -formatter friendly ./...

fasttest: lint
	$(DE) mkdir var || true
	$(DE) go test -coverpkg=./... -p 1 -count 1 --parallel 1 -cover -coverprofile var/coverage.out ./...
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html
	$(DB) bru run --env "Docker Internal" --exclude-tags=ws

test: init-dev fasttest docker-down-clean

ci-test: test
