# Workflow (backend y web)

**Versión actual:** `0.1.0` (ver también `composer.json` y [`doc/project/versions.json`](../doc/project/versions.json)).

Aplicación **Laravel 13** con interfaz **Vue + Inertia**, API REST bajo `/api` para el cliente **Workflow Desktop** y funciones web para PMO, Coordinación y Jefatura de proyecto.

## Stack

- PHP 8.3+, Laravel 13, PostgreSQL
- Vue 3, Inertia, Fortify, Tailwind
- Pest, Laravel Pint

## Requisitos

- Composer, Node.js/npm, extensión PHP `pdo_pgsql` (y `redis` cuando actives colas/caché Redis)

## Arranque rápido

```bash
cp .env.example .env
php artisan key:generate
composer install
npm install
php artisan migrate
php artisan serve
```

En otra terminal: `npm run dev` (Vite).

## Versionado y cambios

- Política: [`doc/project/VERSIONING.md`](../doc/project/VERSIONING.md)
- Historial: [`doc/project/CHANGELOG.md`](../doc/project/CHANGELOG.md)

Tras un cambio publicable, actualizar **CHANGELOG**, **`doc/project/versions.json`**, **`version` en `composer.json`** y esta línea de versión en este README.

## Relación con Workflow Desktop

El escritorio consume **solo la API Laravel** (no duplicar lógica de negocio en el cliente). Detalle: [`.cursor/rules/workflow-laravel-desktop-sync.mdc`](../.cursor/rules/workflow-laravel-desktop-sync.mdc).
