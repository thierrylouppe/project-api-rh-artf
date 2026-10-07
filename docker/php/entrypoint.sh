#!/bin/sh
set -e

cd /var/www/html

# Artisan lit .env ; Docker injecte aussi via env_file
if [ ! -f .env ] && [ -f .env.docker ]; then
  cp .env.docker .env
  echo "Fichier .env créé depuis .env.docker"
fi

# Attendre MySQL
if [ -n "$DB_HOST" ]; then
  echo "Attente de MySQL ($DB_HOST:${DB_PORT:-3306})..."
  i=0
  while [ "$i" -lt 60 ]; do
    if php -r "try { new PDO('mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: '3306'), getenv('DB_USERNAME'), getenv('DB_PASSWORD') ?: ''); exit(0); } catch (Throwable \$e) { exit(1); }" 2>/dev/null; then
      echo "MySQL est prêt."
      break
    fi
    i=$((i + 1))
    sleep 2
  done
fi

if [ ! -f vendor/autoload.php ]; then
  echo "Installation des dépendances Composer..."
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -z "$APP_KEY" ]; then
  echo "Génération de APP_KEY..."
  php artisan key:generate --force --no-interaction
  # Recopie la clé générée vers .env.docker si présent
  if [ -f .env ] && [ -f .env.docker ]; then
    KEY=$(grep '^APP_KEY=' .env | head -1)
    if [ -n "$KEY" ]; then
      sed -i.bak "s|^APP_KEY=.*|$KEY|" .env.docker && rm -f .env.docker.bak
    fi
  fi
fi

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
  echo "Exécution des migrations..."
  php artisan migrate --force --no-interaction
fi

exec "$@"
