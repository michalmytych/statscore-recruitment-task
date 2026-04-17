<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\FoulEvent;
use App\Match\Repository\EventRepositoryInterface;

final readonly class FoulEventHandler
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
    ) {}

    public function __invoke(FoulEvent $event): array
    {
        $eventData = $event->__serialize();

        $this->eventRepository->saveEvent($eventData);

        return $eventData;
    }
}
