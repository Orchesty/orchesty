ENV?=stage
PORTAL_API_URL?=http://127.0.0.88:8000

IMAGE := pipes-docs-builder:latest

builder-image:
	cd docker && \
	docker buildx build --platform=linux/amd64 -f Dockerfile -t $(IMAGE) --pull --output=type=docker .

build: builder-image
	docker run -t --rm \
		-v $(shell pwd)/../../:/build -w /build/docusaurus/documentation \
		-u $(shell id -u):$(shell id -g) \
		-e PORTAL_API_URL=${PORTAL_API_URL} $(IMAGE) \
		bash -c 'pnpm install && pnpm build'

deploy:
	@if [ $(shell ls -1 ./build/ | wc -l) -lt 4 ]; then \
		echo "./build/ is not populated, not deploying"; \
		exit 1; \
	fi
	find ./build -regextype egrep -regex '.*\.(html|js)$$' -exec sed 's|%PORTAL_API_URL%|${PORTAL_API_URL}|g' -i {} \;
	aws s3 sync --acl public-read --delete ./build/ s3://pipes-website-${ENV}/

test:
	pnpm run build