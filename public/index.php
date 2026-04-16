<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\StatisticsManager;
use Api\MatchController;
use DI\Bridge\Slim\Bridge;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

$app = Bridge::create();

$app->post('/event', [MatchController::class, 'storeEvent']);

$app->get('/statistics', function (Request $request, Response $response): Response {
    $statsManager = new StatisticsManager(__DIR__ . '/../storage/statistics.txt');

    $queryParams = $request->getQueryParams();
    $matchId = $queryParams['match_id'] ?? null;
    $teamId = $queryParams['team_id'] ?? null;

    if (!$matchId) {
        $response->getBody()->write(json_encode([
            'error' => 'match_id is required',
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(400);
    }

    $payload = ['match_id' => $matchId];

    if ($teamId) {
        $payload['team_id'] = $teamId;
        $payload['statistics'] = $statsManager->getTeamStatistics($matchId, $teamId);
    } else {
        $payload['statistics'] = $statsManager->getMatchStatistics($matchId);
    }

    $response->getBody()->write(json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();
