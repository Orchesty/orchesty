#!/bin/bash

set -x -e

rm -rf ccpp
git clone -b dev git@gitlab.hanaboso.net:pipes/pipes.git ccpp
pushd ccpp

git filter-branch --tree-filter 'find clients -mindepth 1 -not -regex clients\/clever-connectors.* -delete || true' --prune-empty

git remote add cm1 git@gitlab.hanaboso.net:pipes/client-cm-mirror.git
git remote add cm2 cmgit@dev.clevermonitor.com:clever-connect

git remote add cm ~/cm.git
git push cm1 dev -f
git push cm2 dev -f

popd
rm -rf ccpp
