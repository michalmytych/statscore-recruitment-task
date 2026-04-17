<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Api\MatchController;
use Api\StatisticsController;
use DI\Bridge\Slim\Bridge;

$app = Bridge::create();

$app->post('/event', [MatchController::class, 'storeEvent']);
$app->get('/statistics', [StatisticsController::class, 'get']);

$app->run();
