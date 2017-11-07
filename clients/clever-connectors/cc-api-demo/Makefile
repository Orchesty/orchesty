.PHONY: init composer-update

DC=docker-compose
DE=docker-compose exec app
IMAGE_API_DEMO_FPM=dkr.hanaboso.net/pipes/client-cm/api-demo:latest

.env:
	sed -e "s/{DEV_UID}/$(shell id -u)/g; s/{DEV_USERNAME}/$(shell id -un)/g;" .env.dist > .env

init: .env
	$(DC) up -d --force-recreate
	$(DE) composer install

build: .env
	: Disable dependency symlinking in composer and do a fresh install && \
	rm -rf vendor && \
	for EXT in json lock; do \
		sed -r 's/("symlink": )true/\1false/g' composer.$${EXT} > composer.prod.$${EXT}; \
	done && \
	: Build the app && \
	docker-compose run --rm -e COMPOSER=composer.prod.json app composer install --optimize-autoloader && \
	: Build an app docker image from the artifacts && \
	docker build -t ${IMAGE_API_DEMO_FPM} .

push:
	docker push $(IMAGE_API_DEMO_FPM)
