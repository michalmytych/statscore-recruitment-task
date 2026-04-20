<?php

declare(strict_types=1);

namespace App\Match\Event;

use App\Common\EventInterface;
use App\Match\VO\MatchEventTime;

final readonly class FoulEvent implements EventInterface
{
    public function __construct(
        public string $matchId,
        public string $teamAtFaultId,
        public string $playerAtFault,
        public string $affectedPlayer,
        public MatchEventTime $eventTime,
    ) {}

    public function __serialize(): array
    {
        return [
            'type' => 'foul',
            'timestamp' => $this->eventTime->timestamp,
            'data' => [
                'match_id' => $this->matchId,
                'team_id' => $this->teamAtFaultId,
                'player_at_fault' => $this->playerAtFault,
                'affected_player' => $this->affectedPlayer,
                'second' => $this->eventTime->occurenceSeconds,
                'minute' => $this->eventTime->occurenceMinutes,
            ],
        ];
    }
}
