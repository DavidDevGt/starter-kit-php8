.DEFAULT_GOAL := help
COMPOSE        = docker compose
PHP            = $(COMPOSE) exec app php
COMPOSER       = $(COMPOSE) exec app composer

.PHONY: help up down build restart install migrate rollback test analyse shell logs clean setup

help: ## Show available targets
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

## ── Environment ──────────────────────────────────────────────────────────────

up: ## Start all containers in detached mode
	$(COMPOSE) up -d

down: ## Stop and remove containers (keeps volumes)
	$(COMPOSE) down

build: ## Build / rebuild images
	$(COMPOSE) up -d --build

restart: ## Restart all containers
	$(COMPOSE) restart

## ── Application ──────────────────────────────────────────────────────────────

install: ## Install Composer dependencies (no-dev in production)
	$(COMPOSER) install --no-interaction --prefer-dist --optimize-autoloader

migrate: ## Run pending migrations
	$(PHP) database/migrate.php run

rollback: ## Rollback last migration batch
	$(PHP) database/migrate.php rollback

## ── Quality ──────────────────────────────────────────────────────────────────

test: ## Run PHPUnit test suite
	$(COMPOSER) test

analyse: ## Run PHPStan static analysis (level 5)
	$(COMPOSE) exec app vendor/bin/phpstan analyse

## ── Utilities ────────────────────────────────────────────────────────────────

shell: ## Open a bash shell inside the app container
	$(COMPOSE) exec app sh

logs: ## Tail logs from all containers
	$(COMPOSE) logs -f

clean: ## Remove containers, volumes, and orphans
	$(COMPOSE) down -v --remove-orphans

## ── First-run ────────────────────────────────────────────────────────────────

setup: ## Full first-run setup (copy .env, build, install, migrate)
	@bash bin/setup
