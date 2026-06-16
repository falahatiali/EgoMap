#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

COMPOSE_FILE="docker-compose.prod.yaml"

if docker compose version >/dev/null 2>&1; then
    DC=(docker compose -f "$COMPOSE_FILE")
else
    DC=(docker-compose -f "$COMPOSE_FILE")
fi

log() {
    echo "==> $1"
}

wait_for_database() {
    log "Waiting for MySQL to become healthy..."

    for _ in $(seq 1 30); do
        if docker inspect -f '{{.State.Health.Status}}' egomap_db 2>/dev/null | grep -qx healthy; then
            return 0
        fi

        sleep 2
    done

    echo "MySQL did not become healthy in time." >&2
    exit 1
}

log "Syncing production environment file..."
cp ../envFiles/.env.ego .env

log "Updating code from origin/main..."
git config --global --add safe.directory "$ROOT_DIR"
git fetch origin main

if ! git pull origin main; then
    log "git pull failed — resetting to origin/main"
    git reset --hard origin/main
fi

log "Building and starting containers..."
"${DC[@]}" up -d --build

wait_for_database

log "Building frontend assets..."
"${DC[@]}" --profile build run --rm egomap_node sh -c "npm i && npm run build"

log "Installing PHP dependencies..."
"${DC[@]}" exec -T egomap composer install --no-interaction --optimize-autoloader

log "Running migrations and seeders..."
"${DC[@]}" exec -T egomap php artisan migrate --seed --force

log "Linking public storage..."
"${DC[@]}" exec -T egomap php artisan storage:link --force 2>/dev/null || true

log "Clearing caches..."
"${DC[@]}" exec -T egomap php artisan optimize:clear

log "Restarting queue workers..."
"${DC[@]}" restart egomap_supervisor

log "Deployment finished successfully."
