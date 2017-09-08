#!/bin/bash

if [ -z "$1" ]; then
  echo "Provide docker network host IP, that ssh-agent should bind to, as a script parameter"
  exit 1
fi

echo
echo execute before running docker infrastructure:
echo export SSH_AUTH_HOST=$1
echo
socat -d TCP-LISTEN:2214,reuseaddr,fork,bind=$1 UNIX-CLIENT:$SSH_AUTH_SOCK
echo "SSH agent forwarding's ended"
