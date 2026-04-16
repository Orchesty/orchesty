DC=docker-compose exec -T ui
IMAGE=dkr.hanaboso.net/pipes/pipes/enterprise-frontend:$(TAG)

docker-compose.ci.yml:
	# Comment out any port
	# sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

init:
	# docker-compose up -d --force-recreate
	# $(DC) pnpm install

rebuild:
	cp .dockerignore ../.dockerignore
	docker buildx build -f Dockerfile --pull --push --platform linux/amd64,linux/arm64/v8 -t $(IMAGE) ../. || rm ../.dockerignore
	rm ../.dockerignore || true

test:
	# $(DC) pnpm run type-check

ci-test: init test
