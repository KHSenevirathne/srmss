<?php

/*
|--------------------------------------------------------------------------
| InfinityFree front controller
|--------------------------------------------------------------------------
| This replaces public/index.php for InfinityFree's single-root (htdocs)
| layout. Your Laravel app lives in   htdocs/laravel/   and the contents of
| Laravel's public/ folder live directly in   htdocs/  . The paths below
| therefore point one level *into* the laravel/ subfolder instead of one
| level up (../) like the default file does.
*/

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/laravel/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/laravel/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/laravel/bootstrap/app.php';

$app->handleRequest(Request::capture());
