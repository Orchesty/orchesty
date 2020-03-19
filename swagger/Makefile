IMAGE=dkr.hanaboso.net/pipes/pipes/swagger

build:
	cp .dockerignore ../.dockerignore
	docker build -f Dockerfile -t ${IMAGE}:${TAG} --build-arg URLS='$(shell cat config.json)' --pull .. || rm ../.dockerignore
	docker push $(IMAGE):${TAG}
	rm ../.dockerignore || true
