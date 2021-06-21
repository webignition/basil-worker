#!/usr/bin/env bash

Services=(
  compiler
  chrome-runner
  firefox-runner
  delegator
  rabbitmq
  postgres
  nginx
  app-web
)

for Service in ${Services[*]}
  do
    if ! sudo docker-compose --env-file .docker-compose.env ps --services --filter "status=running" | grep "$Service"; then
        echo "$Service not ok"
        docker-compose --env-file .docker-compose.env ps
        docker-compose --env-file .docker-compose.env logs "$Service"
        exit 1
    fi
  done
