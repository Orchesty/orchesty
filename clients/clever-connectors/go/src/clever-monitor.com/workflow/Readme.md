#Workflow configuration micro service

Please see micro service description in: https://hanaboso.atlassian.net/wiki/spaces/RAN/pages/233668782/Workflow

### How to prepare development env
```
$ make init
```

How to test:
```
$ make ci-test
```

How to build:
You must add all of your new custom dependencies to Dockerfile where the go get command is called.
```
$ make docker-build
$ make docker-push
```
