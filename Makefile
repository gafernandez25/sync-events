.PHONY: run up install env key migrate permissions cache-clear

run: up install env key migrate permissions cache-clear
	@echo "Application ready."
	@echo "API available at: http://localhost:8083"

up:
	docker compose down
	docker compose up -d --build

install:
	docker compose exec php-fpm composer install

env:
	@if [ ! -f .env ]; then cp .env.example .env; fi

key:
	docker compose exec php-fpm php artisan key:generate

migrate:
	docker compose exec -T php-fpm sh -c '\
		rm -f database/database.sqlite && \
		touch database/database.sqlite && \
		php artisan migrate --force'

permissions:
	docker compose exec php-fpm chmod -R 775 storage bootstrap/cache database
	docker compose exec php-fpm chown -R www-data:www-data storage bootstrap/cache database

cache-clear:
	docker compose exec php-fpm php artisan config:clear
