<?php

declare(strict_types=1);

namespace App\Match\Events;

use InvalidArgumentException;

final readonly class MatchEventTime
{
    public function __construct(
        public int $occurenceSeconds,
        public int $occurenceMinutes,
        public ?int $timestamp
    ) {
        if ($occurenceSeconds < 0) {
            throw new InvalidArgumentException('Event occurence seconds must be >= 0');
        }

        if ($occurenceMinutes < 0) {
            throw new InvalidArgumentException('Event occurence minutes must be >= 0');
        }

        if ($timestamp !== null && $timestamp < 0) {
            throw new InvalidArgumentException('Event timestamp must be >= 0');
        }
    }
}
