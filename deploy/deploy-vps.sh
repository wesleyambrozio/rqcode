#!/usr/bin/env bash
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/rqcode}"
REPO_URL="${REPO_URL:-https://github.com/wesleyambrozio/rqcode.git}"
BRANCH="${BRANCH:-main}"

if [ ! -d "$APP_DIR/.git" ]; then
  mkdir -p "$APP_DIR"
  git clone --branch "$BRANCH" "$REPO_URL" "$APP_DIR"
fi

cd "$APP_DIR"
git fetch origin
git checkout "$BRANCH"
git pull --ff-only origin "$BRANCH"

composer install --no-dev --optimize-autoloader

mkdir -p storage/cache storage/logs storage/sessions public/assets/uploads
chmod -R ug+rw storage public/assets/uploads

if [ ! -f .env ]; then
  cp .env.example .env
  echo "Arquivo .env criado em $APP_DIR. Configure banco, URL e e-mail antes de rodar migrations."
  exit 2
fi

php bin/migrate.php
php -r "require 'vendor/autoload.php'; echo 'RQCode OK', PHP_EOL;"

echo "Deploy finalizado. Configure o DocumentRoot do site para: $APP_DIR/public"
