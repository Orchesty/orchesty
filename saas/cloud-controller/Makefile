IMAGE=dkr.hanaboso.net/pipes/pipes/saas-cloud-controller:$(TAG)
API_URL ?= http://localhost:8080

DC=docker-compose
DE=docker-compose exec -T app

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		.env.dist >> .env; \

build:
	docker buildx build --pull --push --platform linux/amd64 -t $(IMAGE) .

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate --remove-orphans

docker-down-clean: .env
	$(DC) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g; s/^(\s+- \$$\{GOPATH\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

init-dev: docker-up-force

lint:
	$(DE) go fmt ./...
	excludes='';\
	for file in $$(ls -R $$(find . -type f ) | grep test.go); do\
		excludes="$${excludes} -exclude $$(echo $${file} | cut -c 3-)";\
	done;\
	$(DE) revive -config config.toml $${excludes} -formatter friendly ./...

fasttest: lint
	$(DE) mkdir var || true
	$(DE) go test -p 8 --failfast -cover -coverpkg=./... -coverprofile var/coverage.out ./...
	$(DE) go tool cover -html=var/coverage.out -o var/coverage.html

test: init-dev fasttest docker-down-clean

ci-test: test

deploy-instance:
	curl -s -X POST "$(API_URL)/instance" \
		-H "Content-Type: application/json" \
		-d '{"instanceInfo":{"instanceDisplayName":"Prod Instance orchesty","instanceUrlPrefix":"prod","forceInstanceId":"orchesty"},"instanceCredentials":{"instanceId":"aa","instanceSecret":"bbb"},"customizations":{"workers":[{"name":"sys-worker","image":"dkr.hanaboso.net/pipes/pipes/enterprise-system-worker:3.0-rc2","sdkType":"nodejs"},{"name":"cloud-worker","image":"dkr.hanaboso.net/orchesty/orchesty-cloud/cloud-worker:master","sdkType":"nodejs"}],"resourceLimits":{"enabled":true,"cpu":"4000","memory":"4048","topologySlots":10,"messages":0,"storageGb":0,"trashDuplication":0},"features":{"traceAuditing":true,"enterpriseDashboards":true,"auditLogs":true,"pulse":true},"trialEndsAt":"2026-12-31T23:59:59Z","rateLimits":{"enabled":false,"second":100}}}' | jq

update-instance:
	curl -s -X PATCH "$(API_URL)/instance" \
		-H "Content-Type: application/json" \
		-d '{"instance":"instance-orchesty","instanceDisplayName":"Prod Instance orchesty","customizations":{"workers":[{"name":"sys-worker","image":"dkr.hanaboso.net/pipes/pipes/enterprise-system-worker:3.0-rc2","sdkType":"nodejs"},{"name":"cloud-worker","image":"dkr.hanaboso.net/orchesty/orchesty-cloud/cloud-worker:master","sdkType":"nodejs"}],"resourceLimits":{"enabled":true,"cpu":"4000","memory":"4048","topologySlots":10,"messages":0,"storageGb":0,"trashDuplication":0},"features":{"traceAuditing":true,"enterpriseDashboards":true,"auditLogs":true,"pulse":true},"trialEndsAt":"2026-12-31T23:59:59Z","rateLimits":{"enabled":false,"second":100},"userName":"admin@example.com"}}' | jq

destroy-instance:
	curl -s -X DELETE "$(API_URL)/instance?instance=instance-cse612e5go" \
		-H "Content-Type: application/json" | jq