<?php

declare(strict_types=1);

namespace App\Match\Exception;

final class DuplicateEventException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Similar event was already registered in the system.');
    }
}
