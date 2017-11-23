.PHONY: lint build

REGISTRY_PREFIX=dkr.hanaboso.net/pipes/pipes
IMAGE=$(REGISTRY_PREFIX)/frontend:$(TAG)
BUILD_IMAGE=$(REGISTRY_PREFIX)/nodejs-build:dev

lint:
	./node_modules/.bin/eslint ./src

build:
	docker pull $(BUILD_IMAGE)
	docker run --rm \
	  -v $(shell pwd):/app \
	  -e DEV_UID=$(shell id -u) \
	  -e DEV_GID=$(shell id -g) \
	  -e SSH_AUTH_SOCK=/tmp/ssh-agent \
	  -u $(shell id -u):$(shell id -g) \
	  -v ${HOME}/.npm:/srv/.npm \
	  $(BUILD_IMAGE) \
	  bash -E -c "socat UNIX-LISTEN:/tmp/ssh-agent,reuseaddr,fork TCP:$(SSH_AUTH_HOST):2214 & sleep .2 && ssh-add -l && npm install && npm run build"

image:
	docker build -t $(IMAGE) .

push:
	docker push $(IMAGE)
