#!/usr/bin/env bash

set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$ROOT_DIR"

export APP_UID="${APP_UID:-$(id -u)}"
export APP_GID="${APP_GID:-$(id -g)}"
PHP_RUNTIME_USER="${PHP_RUNTIME_USER:-www-data}"
PHP_RUNTIME_GROUP="${PHP_RUNTIME_GROUP:-www-data}"

COMPOSE_FILE="docker-compose.prod.yaml"

if docker compose version >/dev/null 2>&1; then
    DC=(docker compose -f "$COMPOSE_FILE")
else
    DC=(docker-compose -f "$COMPOSE_FILE")
fi

log() {
    echo "==> $1"
}

apply_shared_permissions() {
    # Owner deploy → git pull works. Group www-data → PHP-FPM can write.
    # chown alone is NOT enough: default 755 gives group only r-x (no write).
    # Directories need 2775 (setgid + group rwx) so www-data can create symlinks/files.
    log "Applying shared permissions (${APP_UID}:${PHP_RUNTIME_GROUP}, setgid dirs)..."

    mkdir -p \
        storage/app/public \
        storage/framework/{cache,sessions,views} \
        storage/logs \
        bootstrap/cache \
        .composer \
        public

    sudo chown -R "${APP_UID}:${PHP_RUNTIME_GROUP}" "$ROOT_DIR"

    sudo find "$ROOT_DIR" -path "$ROOT_DIR/.git" -prune -o -type d -exec chmod 2775 {} +
    sudo find "$ROOT_DIR" -path "$ROOT_DIR/.git" -prune -o -type f -exec chmod 664 {} +

    sudo chmod -R ug+rwx storage bootstrap/cache .composer public
    if [[ -d vendor ]]; then
        sudo chmod -R ug+rwx vendor
    fi

    [[ -f artisan ]] && sudo chmod 775 artisan
    [[ -f deploy.sh ]] && sudo chmod 775 deploy.sh
}

link_public_storage() {
    log "Linking public/storage..."

    mkdir -p storage/app/public

    if [[ -L public/storage ]]; then
        rm -f public/storage
    elif [[ -e public/storage ]]; then
        rm -rf public/storage
    fi

    ln -sfn ../storage/app/public public/storage
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

apply_shared_permissions

log "Updating code from origin/main..."
git config --global --add safe.directory "$ROOT_DIR"
git fetch origin main

if ! git pull origin main; then
    log "git pull failed — resetting to origin/main"
    git reset --hard origin/main
fi

apply_shared_permissions

log "Building and starting containers..."
"${DC[@]}" up -d --build

wait_for_database

log "Building frontend assets..."
"${DC[@]}" --profile build run --rm egomap_node sh -c "npm i && npm run build"

apply_shared_permissions

log "Installing PHP dependencies..."
"${DC[@]}" exec -T egomap composer install --no-dev --no-interaction --optimize-autoloader

apply_shared_permissions

log "Restarting PHP-FPM (required: opcache.validate_timestamps=0)..."
"${DC[@]}" restart egomap

log "Verifying module autoload..."
"${DC[@]}" exec -T egomap php -r '
require "vendor/autoload.php";
$statuses = json_decode((string) file_get_contents("modules_statuses.json"), true, 512, JSON_THROW_ON_ERROR);
foreach ($statuses as $module => $enabled) {
    if (! $enabled) {
        continue;
    }
    $manifestPath = "Modules/{$module}/module.json";
    if (! is_file($manifestPath)) {
        fwrite(STDERR, "Missing module manifest: {$manifestPath}\n");
        exit(1);
    }
    $manifest = json_decode((string) file_get_contents($manifestPath), true, 512, JSON_THROW_ON_ERROR);
    foreach ($manifest["providers"] ?? [] as $provider) {
        if (! class_exists($provider)) {
            fwrite(STDERR, "Autoload failed for enabled module provider: {$provider}\n");
            exit(1);
        }
    }
}
echo "Module autoload OK\n";
'

log "Removing stale bootstrap caches..."
"${DC[@]}" exec -T egomap sh -c 'rm -f bootstrap/cache/modules.php bootstrap/cache/packages.php bootstrap/cache/services.php'

log "Running migrations..."
"${DC[@]}" exec -T egomap php artisan migrate --force

if [[ "${SEED:-0}" == "1" ]]; then
    log "Running database seeders (SEED=1)..."
    "${DC[@]}" exec -T egomap php artisan db:seed --force
fi

log "Linking public storage..."
apply_shared_permissions
if ! "${DC[@]}" exec -T egomap php artisan storage:link --force; then
    link_public_storage
fi

apply_shared_permissions

log "Clearing caches..."
"${DC[@]}" exec -T egomap php artisan optimize:clear

apply_shared_permissions

log "Restarting queue workers..."
"${DC[@]}" restart egomap_supervisor

log "Deployment finished successfully."
