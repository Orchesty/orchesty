#!/bin/bash

cd cmd

go get -u github.com/go-redis/redis
go get -u github.com/stretchr/testify/assert

go build -o ./../build/pf-multi-probe

./../build/pf-multi-probe