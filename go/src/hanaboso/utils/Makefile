.PHONY: go-test install-deps

install-deps:
	go get -u github.com/go-redis/redis github.com/stretchr/testify/assert gopkg.in/mgo.v2/bson

go-test:
	go test ./...

docker-compose.ci.yml:
	# Comment out any port forwarding
	sed -r 's/^(\s+ports:)$$/#\1/g; s/^(\s+- \$$\{DEV_IP\}.*)$$/#\1/g' docker-compose.yml > docker-compose.ci.yml

ci-test:
	docker-compose up -d --force-recreate
	docker-compose exec -T utils make install-deps go-test
