#Requires -Version 5
# -----------------------------------------------------------------------------
# Builds an InfinityFree-ready upload bundle in  <repo>/dist/htdocs .
# Run from anywhere:   powershell -ExecutionPolicy Bypass -File deploy\infinityfree\build-bundle.ps1
#
# It will: build assets, install production-only PHP deps, assemble the htdocs
# layout (public/* at the root, app in laravel/), drop in the InfinityFree
# front controller + .htaccess lockdown + your .env.production, scrub local
# caches, then restore your dev dependencies so local work keeps running.
# -----------------------------------------------------------------------------
$ErrorActionPreference = 'Stop'

$repo    = (Resolve-Path "$PSScriptRoot\..\..").Path
$dist    = Join-Path $repo 'dist'
$web     = Join-Path $dist 'htdocs'
$appDir  = Join-Path $web  'laravel'
$envProd = Join-Path $PSScriptRoot '.env.production'

Write-Host "Repo root : $repo"
Write-Host "Bundle to : $web"

if (-not (Test-Path $envProd)) {
    throw "Missing $envProd`nCopy .env.production.example to .env.production and fill in your InfinityFree DB_* values first."
}

# 1. Front-end assets ---------------------------------------------------------
Push-Location $repo
try {
    Write-Host "`n[1/8] npm run build ..."
    npm run build
    if ($LASTEXITCODE -ne 0) { throw "npm run build failed" }

    Write-Host "`n[2/8] composer install --no-dev ..."
    composer install --no-dev --optimize-autoloader --no-interaction
    if ($LASTEXITCODE -ne 0) { throw "composer install --no-dev failed" }
} finally {
    Pop-Location
}

# 3. Fresh dist ---------------------------------------------------------------
Write-Host "`n[3/8] Assembling htdocs ..."
if (Test-Path $dist) { Remove-Item $dist -Recurse -Force }
New-Item -ItemType Directory -Path $appDir -Force | Out-Null

# 4. public/* -> htdocs root
Copy-Item (Join-Path $repo 'public\*') $web -Recurse -Force

# 5. application -> htdocs/laravel
foreach ($f in 'app','bootstrap','config','database','resources','routes','storage','vendor') {
    Copy-Item (Join-Path $repo $f) (Join-Path $appDir $f) -Recurse -Force
}
foreach ($f in 'artisan','composer.json','composer.lock') {
    Copy-Item (Join-Path $repo $f) (Join-Path $appDir $f) -Force
}

# 6. InfinityFree-specific files
Write-Host "[4/8] Injecting InfinityFree front controller, .htaccess, .env ..."
Copy-Item (Join-Path $PSScriptRoot 'index.php')     (Join-Path $web 'index.php')     -Force
Copy-Item (Join-Path $PSScriptRoot 'app.htaccess')  (Join-Path $appDir '.htaccess')  -Force
Copy-Item $envProd                                  (Join-Path $appDir '.env')       -Force

# 7. Scrub local/env-specific artefacts (keep packages.php & services.php) -----
Write-Host "[5/8] Scrubbing local caches ..."
foreach ($s in 'config.php','events.php') {
    Remove-Item (Join-Path $appDir "bootstrap\cache\$s") -Force -ErrorAction SilentlyContinue
}
Get-ChildItem (Join-Path $appDir 'bootstrap\cache') -Filter 'routes-*.php' -ErrorAction SilentlyContinue | Remove-Item -Force
Remove-Item (Join-Path $appDir 'storage\logs\*.log')                 -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $appDir 'storage\framework\views\*.php')      -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $appDir 'storage\framework\sessions\*')       -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $appDir 'storage\framework\cache\data\*')     -Recurse -Force -ErrorAction SilentlyContinue
Remove-Item (Join-Path $appDir 'database\database.sqlite')           -Force -ErrorAction SilentlyContinue

# 8. Restore dev deps ---------------------------------------------------------
Write-Host "[6/8] Restoring dev dependencies (composer install) ..."
Push-Location $repo
try {
    composer install --no-interaction | Out-Null
} finally {
    Pop-Location
}

# Report ----------------------------------------------------------------------
$count = (Get-ChildItem $web -Recurse -File | Measure-Object).Count
Write-Host "`n[7/8] Done."
Write-Host "[8/8] Bundle: $web"
Write-Host "      Files : $count   (InfinityFree inode limit ~30000)"
Write-Host ""
Write-Host "Next: FTP the CONTENTS of dist\htdocs into InfinityFree's htdocs folder."
Write-Host "See deploy\infinityfree\DEPLOY.md for the upload + database import steps."
