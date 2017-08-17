.PHONY: docker-build docker-push run-tests

DOCKER_DEFAULT_TAG := dev

docker-build: TAG ?= $(DOCKER_DEFAULT_TAG)
docker-build:
	docker build -t python-xml-parser:$(TAG) -t dkr.hanaboso.net/pipes/lib-python/xml-parser:$(TAG) .

docker-push: TAG ?= $(DOCKER_DEFAULT_TAG)
docker-push: docker-build
	@echo '**'
	@echo '** Pushing image to AWS'
	@echo '**'
	$(aws ecr get-login --region us-west-2)
	docker push dkr.hanaboso.net/pipes/lib-python/xml-parser:$(TAG)

run-tests:
	python -m unittest discover -v
