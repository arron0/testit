.DEFAULT_GOAL := help

help:
	@echo "Some helper tasks for managing application"

build:
	docker build --file ./Dockerfile --tag arron/testit-dev ./

composer:
	docker run --rm --interactive --tty \
      --network="bridge" \
      --cpus=2 \
      --volume $(PWD):/usr/src \
      arron/testit-dev composer $(cmd)

composer-install:
	make composer cmd="install"

composer-update:
	make composer cmd="update"

phpcs:
	make composer cmd="phpcs"

phpcsfix:
	make composer cmd="phpcsfix"

phpstan:
	make composer cmd='phpstan'

unit-tests:
	make composer cmd="unit-tests"

ls:
	docker run --rm --interactive --tty \
          --network="host" \
          --volume $(PWD):/usr/src \
          arron/testit-dev composer install
