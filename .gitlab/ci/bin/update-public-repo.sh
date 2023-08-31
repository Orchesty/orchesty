#!/bin/bash
set -ex

declare -A SUBTREES_MATRIX
PUBLIC_REPO=git@github.com:Orchesty/orchesty.git

#BRANCHES="master 0.5.0 1.0.0 1.0.1 1.0.2 1.0.3 1.0.4 1.0.5 1.0.6 2.0.0"
BRANCHES="master"
SUBTREES_MATRIX["0.5.0"]="cron frontend kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point topology-generator"
SUBTREES_MATRIX["1.0.0"]="app-ui counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.1"]="app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.2"]="app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.3"]="app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.4"]="app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.5"]="app-ui bridge counter cron detector fluentd kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator"
SUBTREES_MATRIX["1.0.6"]="app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator"
SUBTREES_MATRIX["2.0.0"]="app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api"
SUBTREES_MATRIX["master"]="app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api"


CURRENT_BRANCH=$(git symbolic-ref --short HEAD)
for BRANCH in $BRANCHES; do
  git checkout $BRANCH
done
git checkout $CURRENT_BRANCH


rm -rf public_repo
git clone $PUBLIC_REPO public_repo
cd public_repo
git remote add source ../
git fetch source

for BRANCH in $BRANCHES; do
  SUBTREES=${SUBTREES_MATRIX[$BRANCH]}

  git checkout -b source-$BRANCH source/$BRANCH || true
  git checkout source-$BRANCH
  git pull --ff-only

  ## Separate commits for specific sub-repo
  for S in $SUBTREES; do
    git subtree split -P $S -b __$S --rejoin
  done


  ## Create new empty branch or checkout to existing one from public repository.
  if git show-ref --quiet refs/remotes/origin/$BRANCH; then
    git checkout -b $BRANCH origin/$BRANCH
  else
    git checkout --orphan $BRANCH
    git rm -rf .
    git commit --allow-empty -m "Initial empty commit"
  fi


  ## Add new changes into the branch. ADD or MERGE depends depends if the sub-repo already exists in the public repository.
  for S in $SUBTREES; do
      if git subtree merge -P $S __$S -m "Subtree merge"; then
          echo "Merging new changes"
      else
          git subtree add -P $S __$S -m "Subtree add"
          echo "Adding new changes"
      fi
  done

  git push origin $BRANCH
  echo "done"
done