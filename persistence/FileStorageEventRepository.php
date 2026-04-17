<?php

namespace Persistence;

use Persistence\FileStorage;
use App\Match\Repository\EventRepositoryInterface;  

final class FileStorageEventRepository implements EventRepositoryInterface
{
    private string $filePath = __DIR__ . '/../storage/events.txt';

    public function saveEvent(array $eventData): void
    {
        $storage = new FileStorage($this->filePath);
        $storage->save($eventData);
    }

    public function findByMatchId(string $matchId): array
    {
        $storage = new FileStorage($this->filePath);

        return array_values(array_filter(
            $storage->getAll(),
            static function (mixed $event) use ($matchId): bool {
                if (!is_array($event)) {
                    return false;
                }

                $data = $event['data'] ?? null;

                if (!is_array($data)) {
                    return false;
                }

                return ($data['match_id'] ?? null) === $matchId;
            }
        ));
    }
}
