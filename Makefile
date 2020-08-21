IMAGE=dkr.hanaboso.net/pipes/pipes/pf-bridge
PUBLIC_IMAGE=hanaboso/pipes-pf-bridge

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)
