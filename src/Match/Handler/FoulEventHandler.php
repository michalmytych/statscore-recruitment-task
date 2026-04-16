<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\FileStorage;
use App\Match\Event\FoulEvent;
use App\StatisticsManager;

final class FoulEventHandler
{
    public function __construct() {}

    public function __invoke(FoulEvent $event): array
    {
        $data = [
            'match_id' => $event->matchId,
            'team_id' => $event->teamAtFaultId,
            'player_at_fault' => $event->playerAtFault,
            'affected_player' => $event->affectedPlayer,
            'second' => $event->eventTime->occurenceSeconds,
            'minute' => $event->eventTime->occurenceMinutes,
        ];

        $eventData = [
            'type' => 'foul',
            'timestamp' => $event->eventTime->timestamp,
            'data' => $data
        ];

        // @TODO REFACTOR
        $storage = new FileStorage(__DIR__ . '/../../../storage/events.txt');
        $statisticsManager = new StatisticsManager(__DIR__ . '/../../../storage/statistics.txt');
        $storage->save($eventData);    
        $statisticsManager->updateTeamStatistics(
            $data['match_id'],
            $data['team_id'],
            'fouls'
        );

        return $eventData;
    }
}
