<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\GoalEvent;
use App\Match\Repository\EventRepositoryInterface;

final readonly class GoalEventHandler
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
    ) {}

    public function __invoke(GoalEvent $event): array
    {
        $eventData = $event->__serialize();

        $this->eventRepository->saveEvent($eventData);

        return $eventData;
    }
}
