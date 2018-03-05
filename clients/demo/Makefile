.PHONY: init-dev init-prod generate

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{DOCKER_SOCKET_PATH}|$(shell test -S /var/run/docker-$${USER}.sock && echo /var/run/docker-$${USER}.sock || echo /var/run/docker.sock)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		.env.dist > .env && \
	../../build/bin/map-branch-image-tags.sh ./branch-image-tag-map $(shell git rev-parse --symbolic-full-name --abbrev-ref HEAD) >> .env

docker-pull-dev:
	$(DC_DEV) pull

init-dev: .env
	$(DC_DEV) up -d --force-recreate --remove-orphans
	$(DE_DEV) monolith-fpm composer global require hirak/prestissimo
	$(DE_DEV) monolith-fpm composer install --ignore-platform-reqs
