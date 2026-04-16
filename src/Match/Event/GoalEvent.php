<?php

declare(strict_types=1);

namespace App\Match\Event;

use App\Common\EventInterface;
use App\Match\Events\MatchEventTime;

final readonly class GoalEvent implements EventInterface
{
    public function __construct(
        public string $matchId,
        public string $teamId,
        public string $scorerPlayer,
        public string $assistingPlayer,
        public MatchEventTime $eventTime,
    ) {
    }
}