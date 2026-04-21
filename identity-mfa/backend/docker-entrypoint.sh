#!/usr/bin/env bash
set -euo pipefail
cd /var/www/html

if [ ! -f .env ]; then
  _jwt="${JWT_SECRET:-dev_secret_change_me}"
  {
    echo "APP_ENV=dev"
    echo "APP_DEBUG=1"
    echo "APP_SECRET=$(openssl rand -hex 32)"
    echo 'DATABASE_URL="postgresql://user:pass@postgres:5432/identity_mfa?serverVersion=14&charset=utf8"'
    echo 'REDIS_URL="redis://redis:6379"'
    echo "JWT_SECRET=${_jwt}"
    echo 'MAILER_DSN=null://null'
  } > .env
fi

if [ ! -f config/jwt/private.pem ]; then
  mkdir -p config/jwt
  openssl genpkey -algorithm RSA -out config/jwt/private.pem -pkeyopt rsa_keygen_bits:2048
  openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout
  chmod 600 config/jwt/private.pem
fi

composer install --no-interaction --prefer-dist --no-scripts --no-security-blocking

i=0
until php bin/console doctrine:query:sql 'SELECT 1' >/dev/null 2>&1; do
  i=$((i + 1))
  if [ "$i" -ge 60 ]; then
    echo "Postgres not reachable after waiting; check DATABASE_URL." >&2
    exit 1
  fi
  echo "Waiting for Postgres... (${i})"
  sleep 2
done

php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration

# Optional Doctrine fixtures for lab users. Default is off so restarts stay non-destructive.
if [ "${LOAD_LAB_FIXTURES:-0}" = "1" ]; then
  php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate || {
    echo "doctrine:fixtures:load failed; load fixtures manually if needed. Continuing with php-fpm." >&2
  }
else
  echo "Fixtures skipped (LOAD_LAB_FIXTURES!=1). To load once: docker compose exec php php bin/console doctrine:fixtures:load --no-interaction --purge-with-truncate"
fi

exec docker-php-entrypoint "$@"
