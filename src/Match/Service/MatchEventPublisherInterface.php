<?php

declare(strict_types=1);

namespace App\Match\Service;

interface MatchEventPublisherInterface
{
    public function publish(PublishedEventDTO $dto): void;
}
