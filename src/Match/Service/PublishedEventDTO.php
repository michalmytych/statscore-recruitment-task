<?php

declare(strict_types=1);

namespace App\Match\Service;

final readonly class PublishedEventDTO
{
    // Currently that's only a signal that clients should fetch the latest state.
    // This solution is the most reliable way to do it while still keeping it simple.
    // The reason for this DTO's existence is so that in the future it would be easier 
    // to add more data without changing the publisher's interface.
    
    public function __construct(
        public string $eventType,
        public string $matchId,
        public string $idempotencyKey
    ) {}
}
