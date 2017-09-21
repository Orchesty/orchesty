.PHONY: docker-up docker-up-force docker-down-clean

DC=docker-compose
DE=docker-compose exec -T sftp

# Docker
docker-up: .env
	$(DC) pull
	$(DC) up -d

docker-up-force: .env
	$(DC) pull
	$(DC) up -d --force-recreate

docker-down-clean: .env
	$(DC) down -v
