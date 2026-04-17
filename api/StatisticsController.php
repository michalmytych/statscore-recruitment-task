<?php

declare(strict_types=1);

namespace Api;

use App\StatisticsManager;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class StatisticsController
{
    public function __construct(
        private ApiHelper $apiHelper
    ) {}

    public function get(Request $request, Response $response): Response
    {
        $statsManager = new StatisticsManager(__DIR__ . '/../storage/statistics.txt');

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
            $payload['statistics'] = $statsManager->getTeamStatistics($matchId, $teamId);
        } else {
            $payload['statistics'] = $statsManager->getMatchStatistics($matchId);
        }

        return $this->apiHelper->getJsonResponse($response, $payload);
    }
}
