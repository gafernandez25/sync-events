.PHONY: run up install env key migrate permissions cache-clear test

run: env up install key permissions cache-clear migrate
	@echo "Application ready."
	@echo "API available at: http://localhost:806"

up:
	docker compose down -v
	docker compose up -d --build

install:
	docker compose exec php-fpm composer install

env:
	@if [ ! -f .env ]; then cp .env.example .env; fi

key:
	docker compose exec php-fpm php artisan key:generate

migrate:
	docker compose exec -T php-fpm sh -c 'php artisan migrate --force'

permissions:
	docker compose exec php-fpm chmod -R 775 storage bootstrap/cache database
	docker compose exec php-fpm chown -R www-data:1000 storage bootstrap/cache database

cache-clear:
	docker compose exec php-fpm php artisan config:clear

test:
	docker compose exec php-fpm php artisan test

sync-events:
	docker compose exec php-fpm php artisan events:sync
