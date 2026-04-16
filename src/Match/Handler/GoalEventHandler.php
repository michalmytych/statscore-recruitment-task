<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\FileStorage;
use App\Match\Event\GoalEvent;
use App\StatisticsManager;

final class GoalEventHandler
{
    public function __invoke(GoalEvent $event): array
    {
        $data = [
            'match_id' => $event->matchId,
            'team_id' => $event->teamId,
            'scorer' => $event->scorerPlayer,
            'assisting_player' => $event->assistingPlayer,
            'second' => $event->eventTime->occurenceSeconds,
            'minute' => $event->eventTime->occurenceMinutes,
        ];

        $eventData = [
            'type' => 'goal',
            'timestamp' => time(),
            'data' => $data
        ];

        // @TODO REFACTOR
        $storage = new FileStorage(__DIR__ . '/../../../storage/events.txt');
        $statisticsManager = new StatisticsManager(__DIR__ . '/../../../storage/statistics.txt');
        $storage->save($eventData);
        $statisticsManager->updateTeamStatistics(
            $data['match_id'],
            $data['team_id'],
            'goals'
        );

        return $eventData;
    }
}
