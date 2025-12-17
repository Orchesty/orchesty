#!/bin/bash
set -ex

PUBLIC_REPO=git@github.com:Orchesty/orchesty.git

BRANCHES="2.1.0 2.1.1"
# BRANCHES="master"

get_subtrees() {
    case $1 in
        0.5.0) echo "cron frontend kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point topology-generator" ;;
        1.0.0) echo "app-ui counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.1) echo "app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.2) echo "app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.3) echo "app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.4) echo "app-ui bridge counter cron detector kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.5) echo "app-ui bridge counter cron detector fluentd kapacitor limiter logstash notification-sender pf-bridge pf-bundles starting-point status-service topology-generator" ;;
        1.0.6) echo "app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator" ;;
        2.0.0) echo "app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api" ;;
        2.1.0) echo "app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api" ;;
        2.1.1) echo "app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api" ;;
        master) echo "app-ui bridge counter cron detector fluentd limiter pf-bundles starting-point topology-generator worker-api" ;;
        *) echo "" ;;
    esac
}


rm -rf public_repo
git clone $PUBLIC_REPO public_repo
cd public_repo
git remote add source ../
git fetch source

for BRANCH in $BRANCHES; do
  SUBTREES=$(get_subtrees $BRANCH)

  git checkout -b source-$BRANCH source/$BRANCH || true
  git checkout source-$BRANCH
  git pull --ff-only

  ## Separate commits for specific sub-repo
  for S in $SUBTREES; do
    git subtree split -P $S -b __$S --rejoin &
  done
  wait


  ## Create new empty branch or checkout to existing one from public repository.
  if git show-ref --quiet refs/remotes/origin/$BRANCH; then
    if git show-ref --quiet refs/heads/$BRANCH; then
      git checkout $BRANCH
    else
      git checkout -b $BRANCH origin/$BRANCH
    fi
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