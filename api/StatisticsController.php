<?php

declare(strict_types=1);

namespace Api;

use App\Match\Projection\StatisticsProjection;
use App\Match\Repository\EventRepositoryInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class StatisticsController
{
    public function __construct(
        private readonly ApiHelper $apiHelper,
        private readonly StatisticsProjection $statisticsProjection,
        private readonly EventRepositoryInterface $eventRepository,
    ) {}

    public function get(Request $request, Response $response): Response
    {
        $queryParams = $request->getQueryParams();
        $matchId = $queryParams['match_id'] ?? null;
        $teamId = $queryParams['team_id'] ?? null;

        if (!$matchId) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'match_id is required',
            ], 400);
        }

        $payload = ['match_id' => $matchId];

        $events = $this->eventRepository->findByMatchId($matchId);

        if ($teamId) {
            $payload['team_id'] = $teamId;
            $payload['statistics'] = $this->statisticsProjection->projectTeam(
                $events,
                $matchId, 
                $teamId
            );
        } else {
            $payload['statistics'] = $this->statisticsProjection->projectMatch(
                $events, 
                $matchId
            );
        }

        return $this->apiHelper->getJsonResponse($response, $payload);
    }
}
