IMAGE?=dkr.hanaboso.net/pipes/pipes/applinth-marketplace-ui

init: .env
	docker-compose up -d --force-recreate
	docker-compose exec app bin/console service:install node-sdk node-sdk:8080
	docker-compose exec app bin/console api-token:create --key "$(shell grep 'ORCHESTY_API_KEY' .env | cut -d "=" -f2)"

rebuild:
	docker build -t ${IMAGE}:${TAG} --pull .
	docker push ${IMAGE}:${TAG}

.env:
	sed -e "s/{DEV_UID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -u); else echo '1001'; fi)/g" \
		-e "s/{DEV_GID}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo $(shell id -g); else echo '1001'; fi)/g" \
		-e "s/{SSH_AUTH}/$(shell if [ "$(shell uname)" = "Linux" ]; then echo '${SSH_AUTH_SOCK}' | sed 's/\//\\\//g'; else echo '\/run\/host-services\/ssh-auth.sock'; fi)/g" \
		.env.dist > .env; \

generate-link:
	echo "http://localhost:8080/?u=$$(node JWEGen.js)"
