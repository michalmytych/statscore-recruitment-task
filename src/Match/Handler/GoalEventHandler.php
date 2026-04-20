<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\GoalEvent;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\EventIdempotencyKeyFactory;
use App\Match\Service\MatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;

final readonly class GoalEventHandler
{
    public function __construct(
        private EventRepositoryInterface $eventRepository,
        private MatchEventPublisherInterface $publisher,
        private EventIdempotencyKeyFactory $idempotencyKeyFactory
    ) {}

    public function __invoke(GoalEvent $event): array
    {
        $eventData = $event->__serialize();

        $eventData['idempotency_key'] = $this->idempotencyKeyFactory->create(
            eventType: 'goal',
            matchId: $event->matchId,
            minute: $event->eventTime->occurenceMinutes,
            second: $event->eventTime->occurenceSeconds
        );        

        $this->eventRepository->saveEvent($eventData);

        $this->publisher->publish(new PublishedEventDTO(
            eventType: 'goal',
            matchId: $eventData['data']['match_id'],
            idempotencyKey: $eventData['idempotency_key']
        ));

        return $eventData;
    }
}
