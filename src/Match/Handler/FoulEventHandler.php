<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\FileStorage;
use App\Match\Event\FoulEvent;

final class FoulEventHandler
{
    public function __construct() {}

    public function __invoke(FoulEvent $event): array
    {
        $eventData = $event->__serialize();

        // @TODO REFACTOR
        $storage = new FileStorage(__DIR__ . '/../../../storage/events.txt');
        $storage->save($eventData);

        return $eventData;
    }
}
