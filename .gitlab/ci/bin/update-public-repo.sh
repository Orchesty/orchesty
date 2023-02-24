#!/bin/bash

PUBLIC_REPO=git@github.com:Orchesty/orchesty.git

SUBTREES="app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api"

MERGE_CMD=merge

# use only first time
#MERGE_CMD=add

set -ex

rm -rf public_repo
git clone $PUBLIC_REPO public_repo
cd public_repo
git remote add source ../
git fetch source

git checkout -b source-dev source/dev || true
git checkout source-dev
git pull --ff-only

for S in $SUBTREES; do
    git subtree split -P $S -b __$S --rejoin
done

git checkout -b dev origin/dev

for S in $SUBTREES; do
    git subtree $MERGE_CMD -P $S __$S -m "Subtree merge"
done

#git push origin dev
