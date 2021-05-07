#!/usr/bin/env bash

BROWSER=$1

EXIT_CODE_COMPILER_FAILED=100
EXIT_CODE_RUNNER_FAILED=101

ICON_FAILED="𐄂"
ICON_PASSED="✓"

LOCAL_SOURCE_PATH="/var/basil/source"
LOCAL_TARGET_PATH="/var/basil/tests"

TEST_FILENAME="test-${BROWSER}.yml"
LOCAL_TEST_PATH="${LOCAL_SOURCE_PATH}/${TEST_FILENAME}"

sed "s/BROWSER/${BROWSER}/g" ./self-test/test.yml | sudo tee ${LOCAL_TEST_PATH}
COMPILE_OUTPUT=$(sudo docker-compose --env-file .docker-compose.env exec -T compiler ./compiler --source=/app/source/${TEST_FILENAME} --target=/app/tests)
if [ $? -ne 0 ]; then
    echo "${ICON_FAILED} compiler test failed"
    exit ${EXIT_CODE_COMPILER_FAILED}
fi
echo "${ICON_PASSED} compiler test passed"

COMPILED_TARGET_LINE=$(echo -e "${COMPILE_OUTPUT}" | grep "/app/tests/Generated")
COMPILED_TARGET=$(echo "${COMPILED_TARGET_LINE/target: /}" | xargs)

sudo docker-compose --env-file .docker-compose.env exec -T ${BROWSER}-runner ./bin/runner --path=${COMPILED_TARGET}
if [ $? -ne 0 ]; then
    echo "${ICON_FAILED} ${BROWSER}-runner test failed"
    exit ${EXIT_CODE_RUNNER_FAILED}
fi
echo "${ICON_PASSED} ${BROWSER}-runner test passed"

sudo rm ${LOCAL_TEST_PATH}
sudo rm ${LOCAL_TARGET_PATH}/*.php

# Verify local source path is empty
LOCAL_SOURCE_PATH_FILE_COUNT=$(ls -A /var/basil/source | wc -l)
if [ $LOCAL_SOURCE_PATH_FILE_COUNT -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_SOURCE_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_SOURCE_PATH} is not empty";
fi

# Verify local target path is empty
TESTS_PATH_FILE_COUNT=$(ls -A /var/basil/tests | wc -l)
if [ $TESTS_PATH_FILE_COUNT -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_TARGET_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_TARGET_PATH} is not empty";
fi
