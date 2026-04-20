<?php

declare(strict_types=1);

namespace MQ;

use App\Match\Service\MatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;

final class NullMatchEventPublisher implements MatchEventPublisherInterface
{
    public function publish(PublishedEventDTO $dto): void
    {
        // Actually that was placeholder and I was about to trash it
        // but it appears that it's a perfect test fake
    }
}
