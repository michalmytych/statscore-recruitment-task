<?php

namespace App;

use App\Common\EventInterface;
use App\Match\Event\FoulEvent;
use App\Match\Event\GoalEvent;
use App\Match\Handler\FoulEventHandler;
use App\Match\Handler\GoalEventHandler;

final readonly class EventHandler
{
    public function __construct(
        private readonly FoulEventHandler $foulEventHandler,
        private readonly GoalEventHandler $goalEventHandler,
    ) {}

    // Simple solution without event->handler mapping
    public function handleEvent(EventInterface $event): array
    {
        if ($event instanceof FoulEvent) {
            return $this->getEventSuccessResponse(
                $this->foulEventHandler->__invoke($event)
            );
        }

        if ($event instanceof GoalEvent) {
            return $this->getEventSuccessResponse(
                $this->goalEventHandler->__invoke($event)
            );
        }

        return $this->getFailedErrorHandlingResponse('Unsupported event type');
    }

    private function getEventSuccessResponse(array $eventData): array
    {
        return [
            'status' => 'success',
            'message' => 'Event saved successfully',
            'event' => $eventData
        ];
    }

    private function getFailedErrorHandlingResponse(string $reason): array
    {
        return [
            'status' => 'error',
            'message' => 'Event processing failed',
            'reason' => $reason
        ];
    }    
}
