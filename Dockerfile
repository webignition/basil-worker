FROM php:7.4-cli-buster

WORKDIR /app

RUN apt-get -qq update && apt-get -qq -y install  \
  automake \
  cmake \
  g++ \
  git \
  libicu-dev \
  libmagickwand-dev \
  libpng-dev \
  librabbitmq-dev \
  libreadline-dev \
  libzip-dev \
  zlib1g-dev \
  pkg-config \
  ssh-client \
  libpq-dev \
  && docker-php-ext-install \
  pdo_pgsql \
  && pecl install amqp imagick xdebug igbinary redis \
  && rm -rf ../rabbitmq-c \
  && docker-php-ext-enable amqp \
  && version=$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;") \
  && rm -rf /var/lib/apt/lists/*

#RUN apt-get update \
#    && apt-get install -y libpq-dev librabbitmq-dev \
#    && docker-php-ext-install pdo_pgsql > /dev/null
#
#RUN pecl install amqp-1.9.3 \
#    && docker-php-ext-enable amqp
#
#RUN apt-get autoremove -y \
#    && apt-get clean \
#    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
#
RUN echo "Install composer"
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN composer --version
#
#RUN echo "Copying source"
#COPY bin/console /app/bin/console
#RUN chmod +x /app/bin/console
#COPY src /app/src
#
RUN echo "Checking platform requirements"
COPY composer.json /app
COPY composer.lock /app
RUN composer check-platform-reqs --ansi
#
#RUN echo "Installing dependencies"
#RUN composer install --no-dev
#RUN rm composer.json
#RUN rm composer.lock

#RUN echo "Checking proxy server platform requirements ${proxy_server_version}"
#RUN curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.json --output composer.json
#RUN curl https://raw.githubusercontent.com/webignition/docker-tcp-cli-proxy/${proxy_server_version}/composer.lock --output composer.lock
#RUN composer check-platform-reqs --ansi
#RUN rm composer.json
#RUN rm composer.lock
#
#RUN echo "Fetching proxy server ${proxy_server_version}"
#RUN curl -L https://github.com/webignition/docker-tcp-cli-proxy/releases/download/${proxy_server_version}/server.phar --output ./server
#RUN chmod +x ./server
#
#CMD ./server
