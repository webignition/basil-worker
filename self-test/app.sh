#!/usr/bin/env bash

PACKAGES="php7.4-cli php7.4-curl php7.4-dom php7.4-mbstring zip"

INITIAL_DIRECTORY=$(echo $PWD)

# Setup
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml build
sudo docker-compose --env-file .docker-compose.env -f docker-compose.yml -f self-test/services.yml up -d

cd ./self-test/app

sudo apt-get -qq update > /dev/null
sudo apt-get -qq install ${PACKAGES} > /dev/null
curl https://getcomposer.org/download/latest-stable/composer.phar --output composer.phar --silent

php composer.phar update --quiet
if [ 0 -ne $? ]; then
    exit $?
fi

php composer.phar check-platform-reqs --quiet
if [ 0 -ne $? ]; then
    exit $?
fi

# Run
php ./vendor/bin/phpunit ./src/ApplicationTest.php
LAST_EXIT_CODE=$?

if [ ${LAST_EXIT_CODE} -eq 0 ]; then
    sleep 10
    sudo docker logs callback-receiver | php ./vendor/bin/phpunit ./src/CallbackReceiverLogTest.php
    LAST_EXIT_CODE=$?
fi

## Teardown
cd $INITIAL_DIRECTORY
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
    echo "Removing all from ${TABLE}"
    sudo docker-compose --env-file .docker-compose.env exec -T -e PGPASSWORD=password! postgres psql -U postgres -d worker-db -c "DELETE FROM ${TABLE}"
  done

sudo apt-get -qq -y remove ${PACKAGES} > /dev/null
sudo apt-get -qq -y autoremove > /dev/null
sudo rm -Rf ./self-test

if [ 0 -ne ${LAST_EXIT_CODE} ]; then
    exit ${LAST_EXIT_CODE}
fi
