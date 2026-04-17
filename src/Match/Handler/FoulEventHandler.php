<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\FoulEvent;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\MatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;

final readonly class FoulEventHandler
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private MatchEventPublisherInterface $publisher
    ) {}

    public function __invoke(FoulEvent $event): array
    {
        $eventData = $event->__serialize();

        $this->eventRepository->saveEvent($eventData);

        $this->publisher->publish(new PublishedEventDTO(
            eventType: 'foul',
            matchId: $eventData['data']['match_id']
        ));

        return $eventData;
    }
}
