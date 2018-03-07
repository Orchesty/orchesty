#Workflow configuration microservice

Please see microservice description in: TODO add confluence link

How to test:
```
    make ci-test
```

How to build:
```
    make docker-build
    make docker-push
```
You must add all of your new custom dependencies to Dockerfile where the go get command is called.
