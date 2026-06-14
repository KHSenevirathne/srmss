# Deploying SRMSS to InfinityFree (free, no credit card)

InfinityFree gives free PHP 8.x hosting **and** a free MySQL database with no
card required : so your app and your SQL data stay together. The catch: there's
no SSH/Composer/cron on the server, so we build everything locally and upload
the finished files by FTP, and we import your database through phpMyAdmin.

## Final layout on the server

```
htdocs/                 <- InfinityFree web root (this is fixed, can't change)
├── index.php           <- our modified front controller (deploy/infinityfree/index.php)
├── .htaccess           <- from public/.htaccess (unchanged)
├── build/  favicon.*  robots.txt  ...   <- rest of public/
└── laravel/            <- the entire app (app, vendor, .env, etc.)
    ├── .htaccess       <- deny-all lockdown (deploy/infinityfree/app.htaccess)
    ├── .env            <- your production env
    ├── vendor/  app/  bootstrap/  config/  database/  routes/  storage/ ...
```

The build script assembles exactly this under `dist/htdocs/`.

---

## Step 1 : Create the InfinityFree account & site

1. Sign up at https://infinityfree.com (email only, no card).
2. Create a new account/site. Either use the free `*.infinityfreeapp.com`
   subdomain it offers, or add your own domain later.
3. Note the subdomain : that's your `APP_URL`.

## Step 2 : Create the MySQL database

1. In the InfinityFree control panel → **MySQL Databases** → create one
   (call it `srmss`). It becomes something like `epiz_12345678_srmss`.
2. Copy these four values : you'll need them next:
   - **MySQL hostname** (e.g. `sql123.infinityfree.com`) → `DB_HOST`
   - **Database name** (`epiz_12345678_srmss`) → `DB_DATABASE`
   - **Username** (`epiz_12345678`) → `DB_USERNAME`
   - **Password** (the account password) → `DB_PASSWORD`

> Note: InfinityFree MySQL only accepts connections **from their own servers**,
> not from your laptop. That's why we import via phpMyAdmin (Step 5) instead of
> running `php artisan migrate` remotely.

## Step 3 : Fill in your production env

```powershell
Copy-Item deploy\infinityfree\.env.production.example deploy\infinityfree\.env.production
```

Open `deploy/infinityfree/.env.production` and set `APP_URL` and the four `DB_*`
values from Step 2. Then generate an app key straight into that file:

```powershell
php artisan key:generate --show   # copy the base64:... output into APP_KEY=
```

(`.env.production` is gitignored, so your key and DB password are never
committed : important for an app-security submission.)

## Step 4 : Build the upload bundle

```powershell
powershell -ExecutionPolicy Bypass -File deploy\infinityfree\build-bundle.ps1
```

This produces `dist/htdocs/`. It temporarily slims `vendor/` to production-only
deps, then restores your dev deps at the end (so tests still run locally).

## Step 5 : Bring your database across (keep your data)

Export your current local MySQL data:

```powershell
# Herd ships mysqldump; if it's not on PATH, use the full path to it.
mysqldump -u srmss -p srmss --no-create-db --default-character-set=utf8mb4 > srmss-dump.sql
# password: esoft_asecw1
```

Import it into InfinityFree:

1. Control panel → **phpMyAdmin** → open your `epiz_..._srmss` database.
2. **Import** tab → choose `srmss-dump.sql` → **Go**.

That copies all your tables + rows up as-is. (If the file is big, gzip it first;
phpMyAdmin accepts `.sql.gz`.)

## Step 6 : Upload the files by FTP

1. Grab the **FTP details** from the panel (FTP hostname, username, password).
2. Use [FileZilla](https://filezilla-project.org/) (free). Connect.
3. Upload **the contents of `dist/htdocs/`** into the server's `htdocs/` folder
   (so `dist/htdocs/index.php` lands at `htdocs/index.php`, and
   `dist/htdocs/laravel/` lands at `htdocs/laravel/`).

This is ~12k small files : expect it to take a while over FTP. Let it finish.

## Step 7 : Set the PHP version

Control panel → **PHP / Select PHP Version** → pick the highest available that is
**8.3 or 8.4** (the app requires PHP ≥ 8.3).

## Step 8 : Enable SSL and confirm the URL

1. Panel → **SSL/TLS** → issue the free certificate for your domain (takes a few
   minutes to propagate).
2. Make sure `APP_URL` in the uploaded `htdocs/laravel/.env` uses `https://`.
   (If you change it, re-upload just that one file.)

## Step 9 : Test

Visit your URL. Log in with a user from your imported database. Click through
the fuel/maintenance logs, reports (PDF), user management.

---

## Troubleshooting

- **500 error / blank page** : temporarily set `APP_DEBUG=true` in
  `htdocs/laravel/.env`, reload, read the error, then set it back to `false`.
  Also check `htdocs/laravel/storage/logs/laravel.log` via the file manager.
- **"No application encryption key"** : `APP_KEY` is missing in the uploaded
  `.env`; confirm it's present.
- **DB connection refused** : re-check `DB_HOST` (must be the `sqlXXX...` host,
  not `localhost`) and that the DB name/user include the `epiz_...` prefix.
- **CSS/JS missing** : the `build/` folder didn't upload, or `APP_URL` is wrong.
  Confirm `htdocs/build/` exists and `APP_URL` matches the site URL/scheme.
- **403 on the homepage** : `htdocs/index.php` must be the root file; make sure
  you uploaded the *contents* of `dist/htdocs`, not the `htdocs` folder inside
  another folder.
- **Writes failing** : `storage/` and `bootstrap/cache/` must be writable
  (755 dirs / 644 files is the InfinityFree default and is fine).

## Known limits on the free plan

- No queue worker or cron → we use `QUEUE_CONNECTION=sync` (jobs run inline) and
  `SESSION_DRIVER=file` / `CACHE_STORE=file` to keep MySQL query volume low.
- Some PHP functions (`exec`, `proc_open`, `symlink`, …) are disabled. Normal
  web requests and DomPDF report generation don't need them, so you're fine.
- Daily hit limits and a per-hour MySQL query cap apply : plenty for a coursework
  demo, not for real traffic.
- `php artisan storage:link` can't run (no CLI, symlinks disabled). Only matters
  if you serve user-uploaded files from the `public` disk; the app currently uses
  the `local` disk, so no action needed.
