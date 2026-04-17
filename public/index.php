<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Api\MatchController;
use Api\StatisticsController;
use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\MatchEventPublisherInterface;
use Persistence\FileStorageEventRepository;
use Persistence\NullMatchEventPublisher;

// Bootstrap the application
$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    EventRepositoryInterface::class => \DI\autowire(FileStorageEventRepository::class),
    MatchEventPublisherInterface::class => \DI\autowire(NullMatchEventPublisher::class),
]);

$app = Bridge::create($containerBuilder->build());

// Routing
$app->post('/event', [MatchController::class, 'storeEvent']);
$app->get('/statistics', [StatisticsController::class, 'get']);

$app->run();
