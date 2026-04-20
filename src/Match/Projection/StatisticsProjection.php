<?php

declare(strict_types=1);

namespace App\Match\Projection;

final readonly class StatisticsProjection
{
    public function projectMatch(iterable $events, string $matchId): array
    {
        return $this->project($events)[$matchId] ?? [];
    }

    public function projectTeam(iterable $events, string $matchId, string $teamId): array
    {
        return $this->projectMatch($events, $matchId)[$teamId] ?? [];
    }

    public function project(iterable $events): array
    {
        $statistics = [];

        foreach ($events as $event) {
            if (!is_array($event)) {
                continue;
            }

            $this->apply($statistics, $event);
        }

        return $statistics;
    }

    private function apply(array &$statistics, array $event): void
    {
        $type = $event['type'] ?? null;
        $data = $event['data'] ?? null;

        if (!is_string($type) || !is_array($data)) {
            return;
        }

        $matchId = $data['match_id'] ?? null;
        $teamId = $data['team_id'] ?? null;

        if (!is_string($matchId) || !is_string($teamId)) {
            return;
        }

        $statistics[$matchId] ??= [];
        $statistics[$matchId][$teamId] ??= [];

        match ($type) {
            'goal' => $statistics[$matchId][$teamId]['goals'] = ($statistics[$matchId][$teamId]['goals'] ?? 0) + 1,
            'foul' => $statistics[$matchId][$teamId]['fouls'] = ($statistics[$matchId][$teamId]['fouls'] ?? 0) + 1,
            default => null,
        };
    }
}
