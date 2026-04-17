<?php

declare(strict_types=1);

namespace Api;

use App\FileStorage;
use App\Match\Projection\StatisticsProjection;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class StatisticsController
{
    public function __construct(
        private readonly ApiHelper $apiHelper,
        private readonly StatisticsProjection $statisticsProjection
    ) {}

    public function get(Request $request, Response $response): Response
    {
        $storage = new FileStorage(__DIR__ . '/../storage/events.txt');

        $queryParams = $request->getQueryParams();
        $matchId = $queryParams['match_id'] ?? null;
        $teamId = $queryParams['team_id'] ?? null;

        if (!$matchId) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'match_id is required',
            ], 400);
        }

        $payload = ['match_id' => $matchId];

        if ($teamId) {
            $payload['team_id'] = $teamId;
            $payload['statistics'] = $this->statisticsProjection->projectTeam($storage->getAll(), $matchId, $teamId);
        } else {
            $payload['statistics'] = $this->statisticsProjection->projectMatch($storage->getAll(), $matchId);
        }

        return $this->apiHelper->getJsonResponse($response, $payload);
    }
}
