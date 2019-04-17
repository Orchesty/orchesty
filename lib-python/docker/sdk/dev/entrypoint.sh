#!/usr/bin/env bash

sudo /etc/init.d/ssh start

exec "$@"
