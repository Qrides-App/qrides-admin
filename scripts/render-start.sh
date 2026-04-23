#!/bin/sh
set -eu

ensure_laravel_writable_paths() {
    mkdir -p \
        bootstrap/cache \
        storage/app/public \
        storage/firebase \
        storage/firebase/branding \
        storage/firebase/branding/logo \
        storage/framework \
        storage/framework/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs

    chown -R www-data:www-data bootstrap/cache storage || true
    chmod -R ug+rwX bootstrap/cache storage || true
}

sync_firebase_credentials() {
    render_secret_path="/etc/secrets/firebase_credentials.json"
    app_credentials_path="storage/firebase/firebase_credentials.json"

    if [ -r "$render_secret_path" ]; then
        mkdir -p storage/firebase
        cp "$render_secret_path" "$app_credentials_path"
        chown www-data:www-data "$app_credentials_path" || true
        chmod 640 "$app_credentials_path" || true
        echo "Firebase credentials copied to $app_credentials_path"
    elif [ -f "$render_secret_path" ]; then
        echo "Firebase credentials secret exists but is not readable by startup user: $render_secret_path"
    fi
}

ensure_laravel_writable_paths
sync_firebase_credentials

php artisan app:ensure-auth-schema --no-interaction || true
php artisan app:reconcile-migrations --no-interaction || true
php artisan migrate --force --no-interaction || true
php artisan app:ensure-super-admin --no-interaction || true
php artisan optimize:clear || true

# optimize:clear/cache:clear can remove framework cache folders. Recreate them
# after cache clearing so Apache/PHP can write file-cache entries safely.
ensure_laravel_writable_paths
sync_firebase_credentials

(while true; do
    php artisan schedule:run --no-interaction || true
    sleep 60
done) &

exec apache2-foreground
