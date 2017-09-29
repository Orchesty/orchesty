.PHONY: init-dev init-prod .env generate

DEV_YML?="docker-compose.dev.yml"

DC_DEV=docker-compose -f $(DEV_YML)
DE_DEV=docker-compose -f $(DEV_YML) exec -T
DC_PROD=docker-compose -f docker-compose.prod.yml
DE_PROD=docker-compose -f docker-compose.prod.yml exec -T


.env:
	@if ! [ -f .env ]; then \
		sed -e "s/{DEV_UID}/$(shell id -u)/g" \
			-e "s/{DEV_GID}/$(shell id -u)/g" \
			.env.dist >> .env; \
	fi;

docker-pull-dev:
	$(DC_DEV) pull

init-dev: .env
	$(DC_DEV) up -d --force-recreate --remove-orphans
	$(DE_DEV) monolith-fpm composer global require hirak/prestissimo
	$(DE_DEV) monolith-fpm composer install --ignore-platform-reqs
	#$(DE_DEV) magento install-sampledata
	#$(DE_DEV) magento install-magento

destroy-dev:
	$(DC_DEV) down -v

composer-update-dev:
	$(DE_DEV) monolith-fpm composer global require hirak/prestissimo
	$(DE_DEV) monolith-fpm composer update --ignore-platform-reqs

init-prod: .env
	$(DC_PROD) up -d --force-recreate
	$(DE_PROD) monolith-fpm composer install --no-dev --optimize-autoloader --ignore-platform-reqs

ci-yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.dev.yml > docker-compose.ci.yml

generate:
	curl -X POST -H content-type=application/json http://127.0.0.66/api/gateway/test/topology/generate
