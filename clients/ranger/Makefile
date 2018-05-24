.PHONY: init-dev

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DE_DEVT=docker-compose -f $(DEV_YML) exec

.env:
	sed -e "s|{DEV_UID}|$(shell id -u)|g" \
		-e "s|{DEV_GID}|$(shell id -u)|g" \
		-e "s|{PROJECT_SOURCE_PATH}|$(shell pwd)|g" \
		.env.dist > .env && \
	../../build/bin/map-branch-image-tags.sh ./branch-image-tag-map $(shell git rev-parse --symbolic-full-name --abbrev-ref HEAD) >> .env

up: .env
	$(DC_DEV) pull
	$(DC_DEV) up -d --force-recreate --remove-orphans

init-dev: .env
	$(DC_DEV) pull
	rm -rf pipes-api/var/cache/*
	$(DC_DEV) up -d --force-recreate --remove-orphans

destroy-dev:
	$(DC_DEV) down -v

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g;' docker-compose.dev.yml > docker-compose.ci.yml
	# Comment/uncomment DEV/STAGE sections
	sed -r '/# BEGIN DEV$$/,/# END DEV$$/d; /# BEGIN STAGE$$/,/# END STAGE$$/s/^(\s*)#/\1/g;' -i docker-compose.ci.yml
