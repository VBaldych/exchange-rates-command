.PHONY: init
init: docker-build composer-install

.PHONY: start
start: docker-up

.PHONY: down
down: docker-down

.PHONY: php-cli
php-cli: php-container

docker-build docker-up docker-down php-container:
	@docker-compose $(CMD)

docker-build: CMD=up -d --build
docker-up: CMD=up -d
docker-down: CMD=down
php-container: CMD=exec php bash

.PHONY: composer
composer composer-install:
	docker compose exec php composer $(CMD)

composer-install: CMD=install