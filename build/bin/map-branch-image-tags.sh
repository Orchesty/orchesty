#!/bin/bash

if [[ $# -ne 2 && $# -ne 3 ]]; then
  echo "Generate ENV vars, used for mapping specific docker image tags per-branch."
  echo "These ENV vars are interpolated to "image:" directives in docker-compose.*.yml"
  echo
  echo "Usage: map-branch-image-tags.sh <map-file> <branch> [-e]"
  echo
  echo "Options:"
  echo "  -e prepend 'export' before assignments"
  echo
  exit 1
fi

# Example map:
# declare -A BRANCH_IMAGE_TAG_MAP=( \
#   [MONOLITH_default]="dev"
#   [MONOLITH_dev]="feat1"
# )

MAP=$1
CURR_BRANCH=$2

PREPEND=""
if [ "$3" == "-e" ]; then
  PREPEND="export "
fi

. $MAP

declare -A OUT

# set defaults
for KEY in "${!BRANCH_IMAGE_TAG_MAP[@]}"; do
  IMAGE=$(echo $KEY | cut -d '_' -f 1)
  if [[ "$KEY" =~ _default$ ]]; then
    OUT["TAG_${IMAGE}"]=${BRANCH_IMAGE_TAG_MAP[${IMAGE}_default]}
  fi
done

# set overrides
for KEY in "${!BRANCH_IMAGE_TAG_MAP[@]}"; do
  IMAGE=$(echo $KEY | cut -d '_' -f 1)
  if [[ "$KEY" =~ _${CURR_BRANCH}$ ]]; then
    OUT["TAG_${IMAGE}"]="${BRANCH_IMAGE_TAG_MAP[$KEY]}"
  fi 
done

echo # Intentional blank line
for KEY in "${!OUT[@]}"; do
  echo "${PREPEND}${KEY}=${OUT[$KEY]}"
done
