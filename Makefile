IMAGE=dkr.hanaboso.net/pipes/pipes/frontend

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
