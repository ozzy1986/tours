# Tours catalog monorepo - convenience commands.
# Two flavours: Laragon (Windows-native) and Docker (compose).

.PHONY: help install setup up down logs api web embeddings test fresh

help:
	@echo "Targets:"
	@echo "  install     Install PHP/JS/Python deps in all apps"
	@echo "  setup       Run migrations + seeders against the local DB"
	@echo "  api         Run Laravel + Filament on :8000 (Laragon)"
	@echo "  web         Run Vike/Vue dev server on :3000 (Laragon)"
	@echo "  embeddings  Run FastAPI on :8001 (Laragon)"
	@echo "  up          docker compose up -d (db, api, queue, web, embeddings)"
	@echo "  down        docker compose down"
	@echo "  logs        docker compose logs -f"
	@echo "  test        Run all test suites (pest + vitest + pytest)"
	@echo "  fresh       Drop + migrate + seed"

install:
	cd apps/api && composer install
	cd apps/web && npm install
	cd services/embeddings && python -m pip install -r requirements.txt

setup:
	cd apps/api && php artisan migrate --force && php artisan db:seed --force

api:
	cd apps/api && php artisan serve --host=0.0.0.0 --port=8000

web:
	cd apps/web && npm run dev

embeddings:
	cd services/embeddings && uvicorn app.main:app --host 0.0.0.0 --port 8001 --reload

up:
	docker compose up -d --build

down:
	docker compose down

logs:
	docker compose logs -f

test:
	cd apps/api && ./vendor/bin/pest
	cd apps/web && npm test -- --run
	cd services/embeddings && pytest

fresh:
	cd apps/api && php artisan migrate:fresh --seed --force
