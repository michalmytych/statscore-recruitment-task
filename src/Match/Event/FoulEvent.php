<?php

declare(strict_types=1);

namespace App\Match\Event;

use App\Common\EventInterface;
use App\Match\Events\MatchEventTime;

final readonly class FoulEvent implements EventInterface
{
    public function __construct(
        public string $matchId,
        public string $teamAtFaultId,
        public string $playerAtFault,
        public string $affectedPlayer,
        public MatchEventTime $eventTime,
    ) {
    }
}