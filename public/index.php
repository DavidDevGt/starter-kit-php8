<?php

declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

use App\Core\Application;

$app = Application::create(BASE_PATH);

$router = $app->router();

require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

$app->run();
