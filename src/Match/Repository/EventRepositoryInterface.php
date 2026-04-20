<?php

declare(strict_types=1);

namespace App\Match\Repository;

interface EventRepositoryInterface
{
    public function saveEvent(array $eventData): void;

    public function findByMatchId(string $matchId): array;
}