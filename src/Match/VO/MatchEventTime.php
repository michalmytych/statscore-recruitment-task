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
        // These exceptions should be all Domain, so they could be mapped
        // to separate messages in the api response but for now it's an overkill
        // (Though they are still a useful hint for a developer in case of handled & logged 500)

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