FROM php:7.4-cli-buster

WORKDIR /app

RUN apt-get -qq update && apt-get -qq -y install  \
  librabbitmq-dev \
  libpq-dev \
  libzip-dev \
  zip \
  && docker-php-ext-install \
  pdo_pgsql \
  zip \
  && pecl install amqp \
  && docker-php-ext-enable amqp \
  && apt-get autoremove -y \
  && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN echo "Install composer"
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version

RUN echo "Checking platform requirements"
COPY composer.json /app
COPY composer.lock /app
RUN composer check-platform-reqs --ansi

RUN echo "Installing dependencies"
RUN composer install --no-dev
RUN rm composer.json
RUN rm composer.lock

RUN echo "Copying source"
COPY bin/console /app/bin/console
RUN chmod +x /app/bin/console
COPY src /app/src
