#!/usr/bin/env bash

BROWSER=$1

EXIT_CODE_COMPILER_FAILED=100
EXIT_CODE_RUNNER_FAILED=101
EXIT_CODE_LOCAL_SOURCE_PATH_NOT_EMPTY=102
EXIT_CODE_LOCAL_COMPILED_TARGET=103

ICON_FAILED="𐄂"
ICON_PASSED="✓"

LOCAL_SOURCE_PATH="/var/basil/source"
LOCAL_TARGET_PATH="/var/basil/tests"

TEST_FILENAME="test-${BROWSER}.yml"
LOCAL_TEST_PATH="${LOCAL_SOURCE_PATH}/${TEST_FILENAME}"

sed "s/{{ BROWSER }}/${BROWSER}/g" ./self-test/fixtures/basil/test.yml | sudo tee ${LOCAL_TEST_PATH}
COMPILE_OUTPUT=$(sudo docker-compose --env-file .docker-compose.env exec -T compiler ./compiler --source=/app/source/${TEST_FILENAME} --target=/app/tests)
if [ $? -ne 0 ]; then
    echo "${ICON_FAILED} compiler test failed"
    exit ${EXIT_CODE_COMPILER_FAILED}
fi
echo "${ICON_PASSED} compiler test passed"

COMPILED_TARGET_LINE=$(echo -e "${COMPILE_OUTPUT}" | grep "/app/tests/Generated")
COMPILED_TARGET=$(echo "${COMPILED_TARGET_LINE/target: /}" | xargs)

NGINX_FIXTURE_PATH=$(echo "${PWD}/self-test/fixtures/http")
docker run --name http-fixtures --network worker-network -p 8080:80 -v ${NGINX_FIXTURE_PATH}:/usr/share/nginx/html:ro -d nginx:1.19

sudo docker-compose --env-file .docker-compose.env exec -T delegator ./bin/delegator  --browser ${BROWSER} ${COMPILED_TARGET}
if [ $? -ne 0 ]; then
    echo "${ICON_FAILED} ${BROWSER} delegator test failed"
    exit ${EXIT_CODE_RUNNER_FAILED}
fi
echo "${ICON_PASSED} ${BROWSER} delegator test passed"

sudo docker rm -f http-fixtures
sudo rm ${LOCAL_TEST_PATH}
sudo rm ${LOCAL_TARGET_PATH}/*.php

# Verify local source path is empty
LOCAL_SOURCE_PATH_FILE_COUNT=$(ls -A /var/basil/source | wc -l)
if [ $LOCAL_SOURCE_PATH_FILE_COUNT -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_SOURCE_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_SOURCE_PATH} is not empty";
    exit ${EXIT_CODE_LOCAL_SOURCE_PATH_NOT_EMPTY}
fi

# Verify local target path is empty
TESTS_PATH_FILE_COUNT=$(ls -A /var/basil/tests | wc -l)
if [ $TESTS_PATH_FILE_COUNT -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_TARGET_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_TARGET_PATH} is not empty";
    exit ${EXIT_CODE_LOCAL_TARGET_PATH_NOT_EMPTY}
fi
