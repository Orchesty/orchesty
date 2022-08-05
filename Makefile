.PHONY: build test fasttest docker-down-clean docker-up-force

IMAGE=dkr.hanaboso.net/pipes/pipes/pf-bridge
PUBLIC_IMAGE=hanaboso/pipes-pf-bridge

docker-up-force: .env
	docker-compose pull
	docker-compose up -d --force-recreate

docker-down-clean: .env
	docker-compose down -v

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)

fasttest:
	docker-compose exec -T test npm install
	docker-compose exec -T test npm run build
	docker-compose exec -T test npm run lint
	docker-compose exec -T test npm run testunit
	docker-compose exec -T test npm run testintegration

test: docker-up-force fasttest

ci-test: test

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		.env.dist >> .env; \
