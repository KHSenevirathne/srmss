# SRMSS : Smart Route Management and Scheduling System

A web application for public transport depots that replaces manual, spreadsheet-based route
planning with a single centralised dashboard. It manages bus routes and stops, vehicles,
drivers, schedules (with automatic conflict detection), fuel and maintenance logs, and
produces operational reports with PDF export.

## Features

- **Route planning** : routes with ordered stops, start/end points and total distance.
- **Scheduling** : daily / weekly / monthly timetables with automatic conflict detection.
- **Fleet & drivers** : vehicle and driver records with search, filtering and validation.
- **Fuel & maintenance** : per-vehicle logs with a "service due" flag.
- **Dashboard** : live trip status, fleet utilisation and operational alerts.
- **Reporting** : trip-completion, route-performance and fuel-trend reports, exportable to PDF.
- **Role-based access** : admin, supervisor and operator roles.

## Tech stack

- PHP 8.4 · Laravel 13 · Livewire 4 · Flux UI · Blade · Alpine.js · Tailwind CSS 4
- MySQL 8 · Composer 2 · Vite
- `spatie/laravel-permission` (roles) · `barryvdh/laravel-dompdf` (PDF export)

## Requirements

- PHP 8.4+ and Composer 2
- Node.js and npm
- A running MySQL 8 server

## Setup

1. Install dependencies:
   ```bash
   composer install
   npm install
   ```
2. Create the database (in MySQL):
   ```sql
   CREATE DATABASE srmss CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```
3. Prepare the environment file and app key:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   Then set the database credentials in `.env` (`DB_DATABASE=srmss`, plus `DB_USERNAME` and
   `DB_PASSWORD`).
4. Build the schema and seed demo data:
   ```bash
   php artisan migrate --seed
   ```

## Running

Use two terminals:

```bash
npm run dev        # builds CSS/JS assets (Tailwind + Flux)
php artisan serve  # serves the app at http://localhost:8000
```

## Demo logins

All three accounts use the password `password`:

| Role | Email | Can |
|---|---|---|
| Admin | admin@srmss.test | Everything, including user management |
| Supervisor | supervisor@srmss.test | Operate (fleet, routes, schedules, fuel) + view |
| Operator | operator@srmss.test | Log fuel/maintenance + view |

## Testing

```bash
php artisan test
```
