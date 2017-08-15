.PHONY: build-dev

build-image:
	bash docker/build/build.sh

build-dev: build-image
	bash docker/dev/build.sh

build-prod: build-image
	bash docker/prod/build.sh