# syntax=docker/dockerfile:1
# Image production website (csirt.pcr.ac.id). Dibangun di VPS: `docker compose build` dari
# apps/website — lihat README.md § Deploy dan docs/ARCHITECTURE.md § 6.
# Versi image di-pin; naikkan secara sadar (baca changelog), sama seperti Nginx edge.

# --- 1. Aset front-end (Vite + Tailwind) ------------------------------------------------------
FROM node:24.21-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
# Seluruh sumber (sesuai .dockerignore) — Tailwind memindai view/kode untuk kelas yang dipakai.
COPY . .
RUN npm run build

# --- 2. Aplikasi: Nginx + PHP-FPM dalam satu container, tanpa root, port 8080 -----------------
# serversideup/php (GPL-3.0 — hanya dijalankan, tidak didistribusikan). Port 8080 = upstream
# `website:8080` di infra/nginx/conf.d/website.conf.
FROM serversideup/php:8.5-fpm-nginx-trixie-v4.5.1 AS production

USER root
# gd + exif: olah foto (UprightImage), intl: Filament/format tanggal. pdo_sqlite sudah bawaan PHP.
RUN install-php-extensions gd exif intl
# Header keamanan tanpa HSTS, dan tanpa real-IP dari CF-Connecting-IP — alasan di tiap file.
COPY docker/nginx/security.conf docker/nginx/remoteip.conf /etc/nginx/server-opts.d/

USER www-data
WORKDIR /var/www/html

# Dependensi PHP dulu (layer ter-cache selama composer.lock tidak berubah).
COPY --chown=www-data:www-data composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-scripts --no-autoloader

COPY --chown=www-data:www-data . .
# Autoload + skrip composer: package:discover dan filament:upgrade (aset Filament ke public/).
RUN composer dump-autoload --optimize --no-dev --no-interaction

COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Folder SQLite (di-mount volume `database`) dan symlink relatif public/storage → storage/app/public
# (volume `storage`) — dibuat di image supaya tidak perlu storage:link saat start.
RUN mkdir -p database/sqlite && ln -s ../storage/app/public public/storage
