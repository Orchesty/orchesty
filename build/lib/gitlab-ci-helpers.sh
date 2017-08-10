#!/bin/bash

function skip_unmodified {
  DIR=$1
  if [ "$DIR" == "" ]; then
    echo 'Usage: skip_unmodified <directory>'
    exit 2
  fi

  NORMALIZED_DIR="$(echo -n ${DIR} | sed 's|/|_|g; s|-|_|g; s|\.|_|g' | tr '[a-z]' '[A-Z]')"

  # Resolve current change time, historical change time and state file path
  CT_DIR="${HOME}/changetracking/${CI_PROJECT_PATH}/${CI_COMMIT_REF_NAME}"
  CT_FILE="${CT_DIR}/TS_${NORMALIZED_DIR}"
  mkdir -p $CT_DIR
  [ -f "$CT_FILE" ] || echo 0 > $CT_FILE
  LAST_DIR_CHANGE=$(git log -1 --date=format:%s "${DIR}" | sed -nr 's/^Date:\s+([0-9]+)$/\1/p')
  LAST_CACHED_CHANGE=$(cat "${CT_FILE}")

  # Decide if the dir has changed or if the build is not forced
  FORCE_VAR_NAME="\$FORCE_${NORMALIZED_DIR}"
  if [[ $LAST_DIR_CHANGE -gt $LAST_CACHED_CHANGE || "$(eval echo ${FORCE_VAR_NAME})" != "" ]]; then
    echo $LAST_DIR_CHANGE > $CT_FILE
  else
    echo "Skipping unmodified path '${DIR}' (set '${FORCE_VAR_NAME}' variable to force-build this target)"
    exit 0
  fi
}
