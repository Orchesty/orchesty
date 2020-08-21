IMAGE=dkr.hanaboso.net/pipes/pipes/frontend
PUBLIC_IMAGE=hanaboso/pipes-frontend

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)
