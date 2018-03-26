#!/bin/bash

set -e

if [ "$1" == "php-fpm" ]; then
	# Run nginx with a slight delay to avoid serving 504 until PHP starts
	# TODO: maybe use some lightweight process control system
	sh -c 'sleep 3; nginx' &
fi

# No typical "exec" here to avoid breaking our delayed nginx start
"$@"
