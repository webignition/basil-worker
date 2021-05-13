#!/usr/bin/env bash

PACKAGES="php7.4-cli php7.4-curl php7.4-dom php7.4-mbstring zip"

EXIT_CODE_COMPOSER_INSTALL_FAILED=100
EXIT_CODE_COMPOSER_PLATFORM_REQUIREMENTS_FAILED=101
EXIT_CODE_PHPUNIT_ASSERTIONS_FAILED=102

INITIAL_DIRECTORY=$(echo $PWD)

# Setup
cd ./self-test/app

sudo apt-get -qq update > /dev/null
sudo apt-get -qq install ${PACKAGES} > /dev/null
curl https://getcomposer.org/download/latest-stable/composer.phar --output composer.phar --silent

php composer.phar update --quiet
if [ 0 -ne $? ]; then
    exit ${EXIT_CODE_COMPOSER_INSTALL_FAILED}
fi

php composer.phar check-platform-reqs --quiet
if [ 0 -ne $? ]; then
    exit ${EXIT_CODE_COMPOSER_PLATFORM_REQUIREMENTS_FAILED}
fi

# Run
php ./vendor/bin/phpunit ./src/ApplicationTest.php
if [ 0 -ne $? ]; then
    exit ${EXIT_CODE_PHPUNIT_ASSERTIONS_FAILED}
fi

cd $INITIAL_DIRECTORY

# Teardown
DB_TABLES=(
  job
  test_configuration
  test
  callback_entity
)

for TABLE in ${DB_TABLES[*]}
  do
    echo "Removing all from ${TABLE}"
    sudo docker-compose --env-file .docker-compose.env exec -T -e PGPASSWORD=password! postgres psql -U postgres -d worker-db -c "DELETE FROM ${TABLE}"
  done

sudo apt-get -qq -y remove ${PACKAGES} > /dev/null
sudo apt-get -qq -y autoremove > /dev/null
sudo rm -Rf ./self-test
