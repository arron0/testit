.DEFAULT_GOAL := help

help:
	@echo "Some helper tasks for managing application"

build:
	docker build --file ./Dockerfile --tag arron/testit-dev ./

composer-install:
	docker run --rm --interactive --tty \
      --volume $(PWD):/usr/src \
      arron/testit-dev composer install

composer-update:
	docker run --rm --interactive --tty \
      --volume $(PWD):/usr/src \
      arron/testit-dev composer update

unit-tests:
	docker run --rm --interactive --tty \
      --volume $(PWD):/usr/src \
      arron/testit-dev composer unit-tests
