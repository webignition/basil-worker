#!/usr/bin/env bash

ICON_FAILED="𐄂"
ICON_PASSED="✓"

sudo cp ./self-test/test.yml /var/basil/source/test.yml
sudo docker-compose --env-file .docker-compose.env exec -T compiler ./compiler --source=/app/source/test.yml --target=/app/tests
COMPILE_TEST_EXIT_CODE=$?
if [ $COMPILE_TEST_EXIT_CODE -ne 0 ]; then
    echo "$ICON_FAILED compiler test failed"
    exit $COMPILE_TEST_EXIT_CODE
fi

echo "$ICON_PASSED compiler test passed"

sudo rm -Rf /var/basil/source/test.yml
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
