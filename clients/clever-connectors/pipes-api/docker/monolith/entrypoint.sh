#!/bin/bash

set -e

if [ "$1" == "php-fpm" ]; then
	# Run nginx with a slight delay to avoid serving 504 until PHP starts
	# TODO: use some lightweight process control system
	sh -c 'sleep 2; nginx' &
fi

exec "$@"
