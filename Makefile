.SILENT:
.PHONY: help


DOCKER_PHP_RUN = docker run -it --rm --name refactor-excercise -v $(shell pwd):/refactor-excercise -w /refactor-excercise php:8.1-cli
DOCKER_COMPOSER_RUN = docker run -it --rm --name refactor-excercise -v $(shell pwd):/refactor-excercise -w /refactor-excercise composer:2.2.3

## This help screen
help:
	printf "Available targets\n\n"
	awk '/^[a-zA-Z\-\_0-9]+:/ { \
		helpMessage = match(lastLine, /^## (.*)/); \
		if (helpMessage) { \
			helpCommand = substr($$1, 0, index($$1, ":")); \
			helpMessage = substr(lastLine, RSTART + 3, RLENGTH); \
			printf "%-32s %s\n", helpCommand, helpMessage; \
		} \
	} \
	{ lastLine = $$0 }' $(MAKEFILE_LIST)

### Setup application
#setup: create-vendor-dir
#	@echo "\n\033[92mSetup completed\033[0m\n"
#	@echo "Laravel Horizon Dashboard (for queues):"
#	@echo "  \033[93m$(HORIZON_URL)\033[0m\n"

## Create vendor directory
create-vendor-dir:
	mkdir -p ./vendor

## Clean-up application data
clean:
	@sudo rm -rf vendor/

## Install vendor packages
composer-install:
	$(DOCKER_COMPOSER_RUN) /bin/sh -c "composer install --no-interaction"

## Update vendor packages
composer-update:
	$(DOCKER_COMPOSER_RUN) /bin/sh -c "composer update --no-interaction"

## Login to php container
cli-php:
	$(DOCKER_PHP_RUN) sh

## Run all static tests and checks
static-tests: php-syntax php-cpd php-cs php-md

## Run all static checks
php-static:
	@echo "\n\033[93mRun static checks\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer static"

## Run php 8.0 syntax check
php-syntax:
	@echo "\n\033[93mRun syntax check\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer syntax"

## Check PHP code style
php-cs:
	@echo "\n\033[93mRun code style check\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer php-cs"

## Fix code style errors
php-cbf:
	@echo "\n\033[93mRun code style check & fix\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer php-cbf"

## Run PHP Mess Detector
php-md:
	@echo "\n\033[93mRun mess detector\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer php-md"

## Run PHP Copy-paste detector
php-cpd:
	@echo "\n\033[93mRun copy-paste detector\033[0m"
	$(DOCKER_PHP_RUN) /bin/sh -c "composer php-cpd"

## Run all tests (optionally only a group. usage: usage: make test group=[group name])
test:
	@echo "\n\033[93mRun PHPUnit\033[0m"
	if  [ -z $(group) ]; \
	then $(DOCKER_PHP_RUN) /bin/sh -c "./vendor/bin/phpunit --order-by=defects --stop-on-failure"; \
	else $(DOCKER_PHP_RUN) /bin/sh -c "./vendor/bin/phpunit --order-by=defects --stop-on-failure --group $(group)"; \
	fi;
