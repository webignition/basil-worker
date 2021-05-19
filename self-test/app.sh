#!/usr/bin/env bash

INITIAL_DIRECTORY=$PWD

# Setup
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml build
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml up -d

cd ./self-test/app || exit

sudo apt-get -qq update > /dev/null
sudo apt-get -qq install php7.4-cli php7.4-curl php7.4-dom php7.4-mbstring zip > /dev/null
curl https://getcomposer.org/download/latest-stable/composer.phar --output composer.phar --silent

if ! php composer.phar update --quiet; then
    exit 1
fi

if ! php composer.phar check-platform-reqs --quiet; then
    exit 1
fi

# Run
php ./vendor/bin/phpunit ./src/ApplicationTest.php
LAST_EXIT_CODE=$?

if [ "$LAST_EXIT_CODE" -eq 0 ]; then
    COUNTER=0
    RETRY_LIMIT=5
    CALLBACK_RECEIVER_LOG_TEST_EXIT_CODE=1

    until [ $COUNTER -gt $RETRY_LIMIT ]
    do
      if [ "$CALLBACK_RECEIVER_LOG_TEST_EXIT_CODE" -ne 0 ]; then
          sleep 3
          sudo docker logs callback-receiver | php ./vendor/bin/phpunit --stop-on-failure ./src/CallbackReceiverLogTest.php
          CALLBACK_RECEIVER_LOG_TEST_EXIT_CODE=$?
      fi

      COUNTER=$((COUNTER + 1 ))
    done

    LAST_EXIT_CODE=$CALLBACK_RECEIVER_LOG_TEST_EXIT_CODE
fi

## Teardown
cd "$INITIAL_DIRECTORY" || exit
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml stop http-fixtures
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml stop callback-receiver
sudo docker-compose --env-file .docker-compose.env up -d --remove-orphans

DB_TABLES=(
  "job"
  "test"
  "test_configuration"
  "callback_entity"
  "source"
)

for TABLE in ${DB_TABLES[*]}
  do
    echo "Removing all from $TABLE"
    sudo docker-compose --env-file .docker-compose.env exec -T -e PGPASSWORD=password! postgres psql -U postgres -d worker-db -c "DELETE FROM ${TABLE}"
  done

sudo apt-get -qq -y remove php7.4-cli php7.4-curl php7.4-dom php7.4-mbstring zip > /dev/null
sudo apt-get -qq -y autoremove > /dev/null
sudo rm -Rf ./self-test

if [ 0 -ne "$LAST_EXIT_CODE" ]; then
    exit 1
fi
