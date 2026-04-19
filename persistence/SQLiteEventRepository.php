<?php

declare(strict_types=1);

namespace Persistence;

use App\Match\Exception\DuplicateEventException;
use App\Match\Repository\EventRepositoryInterface;
use PDO;
use PDOException;
use RuntimeException;

final class SQLiteEventRepository implements EventRepositoryInterface
{
    private PDO $connection;

    public function __construct(?string $databasePath = null)
    {
        $databasePath ??= __DIR__ . '/../db/events.sqlite';

        $this->connection = new PDO('sqlite:' . $databasePath);
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    public function saveEvent(array $eventData): void
    {
        $type = $eventData['type'] ?? null;
        $timestamp = $eventData['timestamp'] ?? null;
        $data = $eventData['data'] ?? null;

        if (!is_string($type) || !is_array($data)) {
            throw new RuntimeException('Event data has no event type oris invalid');
        }

        $minute = $data['minute'] ?? null;
        $second = $data['second'] ?? null;

        $matchId = $data['match_id'] ?? null;
        $payloadJson = json_encode($data);
        if (!is_string($matchId) || $payloadJson === false) {
            throw new RuntimeException('Event payload is invalid');
        }

        $idempotencyKey = $eventData['idempotency_key'] ?? null;
        if (!is_string($type) || !is_array($data)) {
            throw new RuntimeException('Event data has no event type oris invalid');
        }

        $statement = $this->connection->prepare(
            'INSERT INTO events (
                event_type,
                match_id,
                event_minute,
                event_second,
                event_timestamp,
                idempotency_key,
                payload_json
            ) VALUES (
                :event_type,
                :match_id,
                :event_minute,
                :event_second,
                :event_timestamp,
                :idempotency_key,
                :payload_json
            )'
        );

        $statement->bindValue(':event_type', $type, PDO::PARAM_STR);
        $statement->bindValue(':match_id', $matchId, PDO::PARAM_STR);
        $statement->bindValue(':event_minute', is_int($minute) ? $minute : null, is_int($minute) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':event_second', is_int($second) ? $second : null, is_int($second) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':event_timestamp', is_int($timestamp) ? $timestamp : null, is_int($timestamp) ? PDO::PARAM_INT : PDO::PARAM_NULL);
        $statement->bindValue(':idempotency_key', is_string($idempotencyKey) ? $idempotencyKey : null, is_string($idempotencyKey) ? PDO::PARAM_STR : PDO::PARAM_NULL);
        $statement->bindValue(':payload_json', $payloadJson, PDO::PARAM_STR);

        // In real world this would be better placed in domain service as this is not a repo implementation detail,
        // but for the sake of demonstration this will do
        try {
            $statement->execute();
        } catch (PDOException $e) {
            $isIdempotencyConflict = ($e->errorInfo[1] ?? null) === 19 && str_contains($e->getMessage(), 'events.idempotency_key');
            if ($isIdempotencyConflict) {
                throw new DuplicateEventException();
            }
            throw $e;
        }
    }

    public function findByMatchId(string $matchId): array
    {
        $statement = $this->connection->prepare(
            'SELECT event_type, event_timestamp, idempotency_key, payload_json
            FROM events
            WHERE match_id = :match_id
                ORDER BY id ASC
        '
        );
        $statement->bindValue(':match_id', $matchId, PDO::PARAM_STR);
        $statement->execute();

        // Basic way of mapping rows to shape expected by API
        // That would be not necessary with ORM but I didn't want to complicate things
        $events = [];
        foreach ($statement->fetchAll() as $row) {
            $payload = json_decode($row['payload_json'], true);

            if (!is_array($payload)) {
                continue;
            }

            $event = [
                'type' => $row['event_type'],
                'timestamp' => $row['event_timestamp'] !== null ? (int) $row['event_timestamp'] : null,
                'data' => $payload,
            ];

            if (is_string($row['idempotency_key']) && $row['idempotency_key'] !== '') {
                $event['idempotency_key'] = $row['idempotency_key'];
            }

            $events[] = $event;
        }

        return $events;
    }
}
