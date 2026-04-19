<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Api\MatchController;
use Api\StatisticsController;
use DI\ContainerBuilder;
use DI\Bridge\Slim\Bridge;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\MatchEventPublisherInterface;
use MQ\RabbitMQMatchEventPublisher;
use Persistence\SQLiteEventRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Bootstrap the application
$databasePath = __DIR__ . '/../db/events.sqlite';

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    EventRepositoryInterface::class => \DI\factory(
        static function () use ($databasePath): SQLiteEventRepository {
            return new SQLiteEventRepository($databasePath);
        }
    ),
    MatchEventPublisherInterface::class => \DI\autowire(RabbitMQMatchEventPublisher::class),
]);

$app = Bridge::create($containerBuilder->build());

// Routing
$app->post('/event', [MatchController::class, 'storeEvent']);
$app->get('/statistics', [StatisticsController::class, 'get']);

// Realtime client demo
$app->get('/demo', function (Request $request, Response $response) {
    $response->getBody()->write(
        file_get_contents(__DIR__ . '/demo.html')
    );
    return $response->withHeader('Content-Type', 'text/html');
    
});

$app->run();
