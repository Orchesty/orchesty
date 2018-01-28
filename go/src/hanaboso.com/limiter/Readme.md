#Limiter microservice

Please see microservice description in: https://hanaboso.atlassian.net/wiki/spaces/PIP/pages/208732177/Limiter

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

 