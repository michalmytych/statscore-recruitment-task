<?php

declare(strict_types=1);

namespace App\Match\Event;

use App\Common\EventInterface;
use App\Match\VO\MatchEventTime;

final readonly class GoalEvent implements EventInterface
{
    public function __construct(
        public string $matchId,
        public string $teamId,
        public string $scorerPlayer,
        public string $assistingPlayer,
        public MatchEventTime $eventTime,
    ) {}

    public function __serialize(): array
    {
        return [
            'type' => 'goal',
            'timestamp' => $this->eventTime->timestamp,
            'data' => [
                'match_id' => $this->matchId,
                'team_id' => $this->teamId,
                'scorer' => $this->scorerPlayer,
                'assisting_player' => $this->assistingPlayer,
                'second' => $this->eventTime->occurenceSeconds,
                'minute' => $this->eventTime->occurenceMinutes,
            ]
        ];
    }
}
