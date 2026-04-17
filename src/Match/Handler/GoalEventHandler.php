<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\GoalEvent;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\MatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;

final readonly class GoalEventHandler
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private MatchEventPublisherInterface $publisher
    ) {}

    public function __invoke(GoalEvent $event): array
    {
        $eventData = $event->__serialize();

        $this->eventRepository->saveEvent($eventData);

        $this->publisher->publish(new PublishedEventDTO(
            eventType: 'goal',
            matchId: $eventData['data']['match_id']
        ));

        return $eventData;
    }
}
