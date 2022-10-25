BASE_IMAGE=dkr.hanaboso.net/pipes/pipes/nodejs-build
PUBLIC_BASE_IMAGE=hanabosocom/nodejs-build

IMAGE?=dkr.hanaboso.net/pipes/pipes/applinth-marketplace-ui

init: .env
	docker-compose up -d --force-recreate

rebuild:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}

build-dev:
	cd docker && docker build -t ${BASE_IMAGE}:${TAG} --pull .
	docker push ${BASE_IMAGE}:${TAG}

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \
