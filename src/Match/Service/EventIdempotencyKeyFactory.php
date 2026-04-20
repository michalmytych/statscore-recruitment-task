<?php

declare(strict_types=1);

namespace App\Match\Service;

final readonly class EventIdempotencyKeyFactory
{
    public function create(
        string $eventType,
        string $matchId,
        int $minute,
        int $second
    ): string {
        // Really basic way of business-level idempotency checking
        // The construction depends on how deep we would like to go
        // Currently it allows one event of each type per match per minute:second
        // In real world it would probably be a ennormous amount of decisions

        return sprintf('%s-%s-%02d-%02d', $eventType, $matchId, $minute, $second);
    }
}