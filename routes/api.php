<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| The routes are separated into dedicated domain files:
| - public.php   : Unauthenticated public & guest endpoints
| - user.php     : Client/User endpoints (authenticated)
| - provider.php : Provider endpoints (authenticated)
| - admin.php    : Administrative endpoints (authenticated & role:admin)
|
*/

require __DIR__.'/public.php';
require __DIR__.'/user.php';
require __DIR__.'/provider.php';
require __DIR__.'/admin.php';
