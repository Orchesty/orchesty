.PHONY: build test fasttest docker-down-clean docker-up-force

IMAGE=dkr.hanaboso.net/pipes/pipes/pf-bridge
PUBLIC_IMAGE=hanaboso/pipes-pf-bridge

docker-up-force:
	docker-compose pull
	docker-compose up -d --force-recreate

docker-down-clean:
	docker-compose down -v

build:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}
	docker tag ${IMAGE}:${TAG} $(PUBLIC_IMAGE):$(TAG)
	docker push $(PUBLIC_IMAGE):$(TAG)

fasttest:
	docker-compose exec -T test npm install
	docker-compose exec -T test npm run build
	docker-compose exec -T test npm run lint
	docker-compose exec -T test npm run testunit
	docker-compose exec -T test npm run testintegration

test: docker-up-force fasttest