#!/bin/bash

sudo ifconfig lo0 alias 127.0.0.66 up
sudo ifconfig lo0 alias 127.0.0.67 up
sudo ifconfig lo0 alias 127.0.0.2 up

keypath="/Users/$USER/.docker/machine/machines/docker-pa/id_rsa"
sshconfigpath=$(pwd)"/mac_ssh_config"

sudo ssh -i $keypath -F $sshconfigpath mac_dsync-dev
