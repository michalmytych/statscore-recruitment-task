<?php

declare(strict_types=1);

namespace App\Match\Handler;

use App\Match\Event\FoulEvent;

final class FoulEventHandler
{
    public function __invoke(FoulEvent $event): void {
        // @TODO
    }
}

