up: docker-up
down: docker-down
restart: down up
app: docker-app
build: docker-build
test: docker-test
analyze: docker-check-format docker-phpstan

docker-up:
	docker-compose up -d --build

docker-down:
	docker-compose down --remove-orphans

docker-down-clear:
	docker-compose down -v --remove-orphans

docker-app:
	docker compose exec php bash

docker-build:
	docker compose build

docker-check-format:
	docker compose exec -T php ./vendor/bin/pint -v --test --dirty

docker-phpstan:
	docker compose exec -T php ./vendor/bin/phpstan analyze

docker-test:
	docker compose exec -T php php artisan optimize:clear
	docker compose exec -T php composer test
