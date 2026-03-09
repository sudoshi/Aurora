.PHONY: up down build fresh logs test lint deploy

up:
	docker compose --profile dev up -d

down:
	docker compose down

build:
	docker compose build

fresh:
	docker compose down -v
	docker compose --profile dev up -d
	docker compose exec php php artisan migrate:fresh --seed

logs:
	docker compose logs -f

test:
	@echo "=== PHP Tests ==="
	cd backend && php artisan test
	@echo "=== Frontend Tests ==="
	cd frontend && npm test
	@echo "=== AI Tests ==="
	cd ai && python -m pytest

lint:
	@echo "=== PHP Lint ==="
	cd backend && ./vendor/bin/pint --test
	cd backend && ./vendor/bin/phpstan analyse
	@echo "=== Frontend Lint ==="
	cd frontend && npx tsc --noEmit
	cd frontend && npx eslint src/

deploy:
	./deploy.sh
