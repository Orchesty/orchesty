.PHONY: builder-image build

builder-image:
	cd builder-image && \
	docker build -t pipes-docs-builder .

build: builder-image
	docker run -t --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder bash -xc ' \
	  phpdoc && \
	  mv /tmp/phpdoc-build/README.md src/docs/sdk/api/index.md && \
	  cat <(echo -e "---\nautotoc: true\n---") src/docs/sdk/api/index.md > /tmp/index && \
	  mv /tmp/index src/docs/sdk/api/index.md && \
	  yarn run build' # && \
	  #aws s3 sync --delete ./build/ s3://pipes-website/'

dev-server: builder-image
	docker run -t --rm -p 127.0.0.1:9083:9080 -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder yarn run dev-server

interactive: builder-image
	docker run -ti --rm -v $(shell pwd)/../:/build -w /build/website -u $(shell id -u):$(shell id -g) pipes-docs-builder bash
