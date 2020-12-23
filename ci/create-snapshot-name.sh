#!/usr/bin/env bash

EXIT_CODE_INVALID_EVENT=1

# push to master
# EVENT_NAME='push' ./create-snapshot-name.sh

# create pull request
# EVENT_NAME='pull_request' PR_NUMBER=101 ./create-snapshot-name.sh

# publish release
# EVENT_NAME='release' RELEASE_TAG_NAME='"0.1"' ./create-snapshot-name.sh

TIMESTAMP=$(date --utc +%Y-%m-%dT%H:%M:%SZ)

if [ $EVENT_NAME = "push" ]; then
  SUFFIX="master"
elif [ $EVENT_NAME = "pull_request" ]; then
  SUFFIX="pull-request-${PR_NUMBER}"
elif [ $EVENT_NAME = "release" ]; then
  SUFFIX="release-$(echo $RELEASE_TAG_NAME | tr -d '"')"
else
  exit $EXIT_CODE_INVALID_EVENT
fi

echo "::set-output name=value::${TIMESTAMP}-${SUFFIX}"
