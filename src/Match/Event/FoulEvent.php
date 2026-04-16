<?php

declare(strict_types=1);

namespace App\Match\Event;

use App\Common\EventInterface;
use App\Match\Events\MatchEventTime;

final readonly class FoulEvent implements EventInterface
{
    public function __construct(
        string $matchId,
        string $teamAtFaultId,
        string $playerAtFault,
        string $affectedPlayer,
        MatchEventTime $eventTime,
    ) {
    }
}