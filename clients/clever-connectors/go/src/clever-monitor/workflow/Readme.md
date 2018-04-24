#Workflow configuration micro service

Please see micro service description in: https://hanaboso.atlassian.net/wiki/spaces/RAN/pages/233668782/Workflow

This micro service respects grpc's protofile https://gitlab.clevermonitor.com/hanaboso/protofiles/blob/master/protos/workflow/WorkflowService.proto

Before any development, check the validity of .protofile if it's up-to-date.
If it is not not updated, download current version and generate new source code from it via `make proto-gen`

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

TODO - update readme
