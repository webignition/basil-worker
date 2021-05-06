#!/usr/bin/env bash

BROWSER=$1

EXIT_CODE_COMPILER_FAILED=100
EXIT_CODE_RUNNER_FAILED=101

ICON_FAILED="𐄂"
ICON_PASSED="✓"

sed "s/BROWSER/${BROWSER}/g" ./self-test/test.yml | sudo tee /var/basil/source/test-${BROWSER}.yml
COMPILE_OUTPUT=$(sudo docker-compose --env-file .docker-compose.env exec -T compiler ./compiler --source=/app/source/test-${BROWSER}.yml --target=/app/tests)
if [ $? -ne 0 ]; then
    echo "$ICON_FAILED compiler test failed"
    exit $EXIT_CODE_COMPILER_FAILED
fi
echo "$ICON_PASSED compiler test passed"

COMPILED_TARGET_LINE=$(echo -e "$COMPILE_OUTPUT" | grep "/app/tests/Generated")
COMPILED_TARGET=$(echo "${COMPILED_TARGET_LINE/target: /}" | xargs)

sudo docker-compose --env-file .docker-compose.env exec -T ${BROWSER}-runner ./bin/runner --path=$COMPILED_TARGET
if [ $? -ne 0 ]; then
    echo "$ICON_FAILED ${BROWSER}-runner test failed"
    exit $EXIT_CODE_RUNNER_FAILED
fi
echo "$ICON_PASSED ${BROWSER}-runner test passed"

sudo rm -Rf /var/basil/source/*.yml
sudo rm -Rf /var/basil/tests/*.php

# Verify /var/basil/source is empty
SOURCE_PATH_FILE_COUNT=$(ls -A /var/basil/source | wc -l)
if [ $SOURCE_PATH_FILE_COUNT -eq 0 ]; then
    echo "$ICON_PASSED /var/basil/source is empty";
else
    echo "$ICON_FAILED /var/basil/source is not empty";
fi

# Verify /var/basil/tests is empty
TESTS_PATH_FILE_COUNT=$(ls -A /var/basil/tests | wc -l)
if [ $TESTS_PATH_FILE_COUNT -eq 0 ]; then
    echo "$ICON_PASSED /var/basil/tests is empty";
else
    echo "$ICON_FAILED /var/basil/tests is not empty";
fi
