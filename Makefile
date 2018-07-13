ENV ?= dev

.PHONY: cs md test all
.DEFAULT_GOAL := all

vendor: composer.json composer.lock
	composer install --prefer-dist --no-progress

cs: vendor
	vendor/bin/phpcs --colors --standard=PSR2 src

csfix: vendor
	vendor/bin/phpcbf --colors --standard=PSR2 src

md: vendor
	vendor/bin/phpmd src text phpmd.xml

test:
	vendor/bin/phpunit

all: cs md test
