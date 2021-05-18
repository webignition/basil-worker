#!/usr/bin/env bash

BROWSER=$1

ICON_FAILED="𐄂"
ICON_PASSED="✓"

LOCAL_SOURCE_PATH="/var/basil/source"
LOCAL_TARGET_PATH="/var/basil/tests"

TEST_FILENAME="test-${BROWSER}.yml"

sed "s/{{ BROWSER }}/${BROWSER}/g" ./self-test/fixtures/basil/test.yml | sudo tee "${LOCAL_SOURCE_PATH}/${TEST_FILENAME}"
COMPILE_OUTPUT=$(sudo docker-compose --env-file .docker-compose.env exec -T compiler ./compiler --source=/app/source/"${TEST_FILENAME}" --target=/app/tests)
COMPILE_EXIT_CODE=$?
if [ "${COMPILE_EXIT_CODE}" -ne 0 ]; then
    echo "${ICON_FAILED} compiler test failed"
    exit 1
fi
echo "${ICON_PASSED} compiler test passed"

COMPILED_TARGET_LINE=$(echo -e "${COMPILE_OUTPUT}" | grep "/app/tests/Generated")
COMPILED_TARGET=$(echo "${COMPILED_TARGET_LINE/target: /}" | xargs)

docker run --name http-fixtures --network worker-network -p 8080:80 -v "${PWD}/self-test/fixtures/http":/usr/share/nginx/html:ro -d nginx:1.19

sudo docker-compose --env-file .docker-compose.env exec -T delegator ./bin/delegator  --browser "${BROWSER}" "${COMPILED_TARGET}"
DELEGATOR_EXIT_CODE=$?
if [ "$DELEGATOR_EXIT_CODE" -ne 0 ]; then
    echo "${ICON_FAILED} ${BROWSER} delegator test failed"
    exit 1
fi
echo "${ICON_PASSED} ${BROWSER} delegator test passed"

sudo docker rm -f http-fixtures
sudo rm ${LOCAL_SOURCE_PATH}/*.yml
sudo rm ${LOCAL_TARGET_PATH}/*.php

# Verify local source path is empty
LOCAL_SOURCE_PATH_FILE_COUNT=$(find "$LOCAL_SOURCE_PATH" | grep '\.yml' -c)
if [ "$LOCAL_SOURCE_PATH_FILE_COUNT" -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_SOURCE_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_SOURCE_PATH} is not empty";
    exit 1
fi

# Verify local target path is empty
TESTS_PATH_FILE_COUNT=$(find "$LOCAL_TARGET_PATH" | grep '\.php' -c)
if [ "$TESTS_PATH_FILE_COUNT" -eq 0 ]; then
    echo "${ICON_PASSED} ${LOCAL_TARGET_PATH} is empty";
else
    echo "${ICON_FAILED} ${LOCAL_TARGET_PATH} is not empty";
    exit 1
fi
