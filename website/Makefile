.PHONY: builder-image build

ENV?=stage
PORTAL_API_URL?=http://127.0.0.88:8000


builder-image:
	cd docker && \
	docker build -t pipes-docs-builder .

build: builder-image
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e PORTAL_API_URL=${PORTAL_API_URL} pipes-docs-builder bash -xc ' \
#	  phpdoc && \
#	  mv /tmp/phpdoc-build/README.md src/docs/sdk/api/index.md && \
	  cat <(echo -e "--- \nautotoc: true \nlayout: main.hbs	\n---") \
	  src/docs/sdk/api/index.md > /tmp/index && \
	  mv /tmp/index src/docs/sdk/api/index.md && \
	  yarn upgrade && \
	  yarn run build'

deploy:
	@if [ $(shell ls -1 ./build/ | wc -l) -lt 4 ]; then \
		echo "./build/ is not populted, not deploying"; \
		exit 1; \
	fi
	find ./build -regextype egrep -regex '.*\.(html|js)$$' -exec sed 's|%PORTAL_API_URL%|${PORTAL_API_URL}|g' -i {} \;
	aws s3 sync --acl public-read --delete ./build/ s3://pipes-website-${ENV}/

dev-server: builder-image
	docker stop website-dev-server || true && \
	docker run -t --rm \
	  --name website-dev-server \
	  -p 127.0.0.1:9084:9080 \
	  -v $(shell pwd)/../:/build \
	  -w /build/website \
	  -u $(shell id -u):$(shell id -g) \
	  -e PORTAL_API_URL=${PORTAL_API_URL} \
	  pipes-docs-builder \
	  bash -xc ' \
	    yarn run dev-server & \
	    yarn run build --dev-server'

interactive: builder-image
	docker run -ti --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder bash

dev-server-debug: builder-image
	docker run -t --rm -p 127.0.0.1:9084:9080 -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e DEBUG=metalsmith:* pipes-docs-builder bash -xc ' \
	  yarn run dev-server & \
	  yarn run build --dev-server'

yarn-build:
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e PORTAL_API_URL=${PORTAL_API_URL} pipes-docs-builder bash -xc 'node build.js'

yarn-install: builder-image
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e PORTAL_API_URL=${PORTAL_API_URL} pipes-docs-builder bash -xc 'yarn install'

yarn-upgrade: builder-image
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) -e PORTAL_API_URL=${PORTAL_API_URL} pipes-docs-builder bash -xc 'yarn upgrade'