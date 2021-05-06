#!/usr/bin/env bash

Services=(
  compiler
  chrome-runner
  firefox-runner
  delegator
  rabbitmq
  postgres
  nginx
  app-handler
  app-web
)

for Service in ${Services[*]}
  do
    if ! sudo docker-compose --env-file .docker-compose.env ps --services --filter "status=running" | grep "$Service"; then
        echo "$Service not ok"
        exit 1
    fi
  done
