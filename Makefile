BASE_IMAGE=dkr.hanaboso.net/pipes/pipes/nodejs-build
PUBLIC_BASE_IMAGE=hanabosocom/nodejs-build

IMAGE=dkr.hanaboso.net/pipes/pipes/app-ui
PUBLIC_IMAGE=hanaboso/pipes-app-ui

init:
	docker-compose up -d --force-recreate

rebuild:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)

build-dev:
	cd docker && docker build -t ${BASE_IMAGE}:${TAG} --pull .
	docker push ${BASE_IMAGE}:${TAG}
	docker tag ${BASE_IMAGE}:${TAG} $(PUBLIC_BASE_IMAGE):$(TAG)
	docker push $(PUBLIC_BASE_IMAGE):$(TAG)
