<?php

declare(strict_types=1);

namespace Persistence;

use App\Match\Service\MatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;

final class NullMatchEventPublisher implements MatchEventPublisherInterface
{
    public function publish(PublishedEventDTO $dto): void
    {
        // @Todo
    }
}
