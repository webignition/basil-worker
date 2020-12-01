#!/usr/bin/env bash

docker build -t "smartassert/basil-worker:${TAG_NAME:-master}" .
