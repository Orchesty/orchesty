.PHONY: builder-image build

ENV?=stage

builder-image:
	cd builder-image && \
	docker build -t pipes-docs-builder .

build: builder-image
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e PORTAL_URL=http://127.0.0.88:8000/installer pipes-docs-builder bash -xc ' \
	  phpdoc && \
	  mv /tmp/phpdoc-build/README.md src/docs/sdk/api/index.md && \
	  cat <(echo -e "--- \nautotoc: true \nlayout: main.hbs	\n---") \
	  src/docs/sdk/api/index.md > /tmp/index && \
	  mv /tmp/index src/docs/sdk/api/index.md && \
	  yarn run build'

deploy:
	@if [ $(shell ls -1 ./build/ | wc -l) -lt 4 ]; then \
		echo "./build/ is not populted, not deploying"; \
		exit 1; \
	fi
	aws s3 sync --acl public-read --delete ./build/ s3://pipes-website-${ENV}/

dev-server: builder-image
	docker run -t --rm -p 127.0.0.1:9084:9080 -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder bash -xc ' \
	  yarn run dev-server & \
	  yarn run build --dev-server'

interactive: builder-image
	docker run -ti --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder bash

dev-server-debug: builder-image
	docker run -t --rm -p 127.0.0.1:9084:9080 -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e DEBUG=metalsmith:* pipes-docs-builder bash -xc ' \
	  yarn run dev-server & \
	  yarn run build --dev-server'
