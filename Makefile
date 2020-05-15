PHP_IMAGE := flipbox/php:73-apache
composer-install:
	composer install --ignore-platform-reqs

test: phpcbf
phpcs: composer-install
	docker run --rm -it -v "${PWD}:/var/www/html" \
	    $(PHP_IMAGE) sh -c "./vendor/bin/phpcs --standard=psr2 --ignore=./src/web/assets/*/dist/*,./src/migrations/m* ./src"
phpcbf: composer-install
	docker run --rm -it -v "${PWD}:/var/www/html" \
	    $(PHP_IMAGE) sh -c "./vendor/bin/phpcbf --standard=psr2 ./src"
