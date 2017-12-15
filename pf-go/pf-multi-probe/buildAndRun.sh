#!/bin/bash

cd cmd

go get -u github.com/go-redis/redis

go build -o ./../build/pf-multi-probe

./../build/pf-multi-probe