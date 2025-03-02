<?php

use Forge\Core\Helpers\App;
use MyApp\Controllers\HomeController;

$router = App::router();
$router->get('/', [HomeController::class, 'index']);