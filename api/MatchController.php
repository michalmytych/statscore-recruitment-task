<?php

declare(strict_types=1);

namespace Api;

use App\EventHandler;
use App\Match\VO\MatchEventTime;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Match\Exception\DuplicateEventException;

final class MatchController
{
    public function __construct(
        private readonly EventHandler $eventHandler,
        private readonly ApiHelper $apiHelper
    ) {}

    public function storeEvent(Request $request, Response $response): Response
    {
        $data = $this->apiHelper->getJsonBody($request);

        if (!is_array($data)) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'Invalid JSON',
            ], 400);
        }

        if (!isset($data['type'])) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'Event type is required',
            ], 400);
        }

        $errors = $this->getEventValidationErrors($data);

        if (count($errors) > 0) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'Validation failed',
                'details' => array_values($errors),
            ], 400);
        }

        // This decision is somehow factory-like but I left it that way to KeEp iT sTuPiD sImPlE
        // As it is also kinda related to business logic that would be probably another reason to move it
        $event = match ($data['type']) {
            'foul' => new \App\Match\Event\FoulEvent(
                matchId: $data['match_id'],
                teamAtFaultId: $data['team_at_fault_id'],
                playerAtFault: $data['player_at_fault'],
                affectedPlayer: $data['affected_player'],
                eventTime: new MatchEventTime(
                    occurenceMinutes: $data['minute'],
                    occurenceSeconds: $data['second'],
                    timestamp: $data['timestamp']
                )
            ),
            'goal' => new \App\Match\Event\GoalEvent(
                matchId: $data['match_id'],
                teamId: $data['team_id'],
                scorerPlayer: $data['scorer'],
                assistingPlayer: $data['assisting_player'],
                eventTime: new MatchEventTime(
                    occurenceMinutes: $data['minute'],
                    occurenceSeconds: $data['second'],
                    timestamp: null
                )
            ),
            default => null,
        };

        if ($event === null) {
            return $this->apiHelper->getJsonResponse($response, [
                'error' => 'Unsupported event type: ' . $data['type'],
            ], 400);
        }

        try {
            $handlerResponse = $this->eventHandler->handleEvent($event);
        } catch (DuplicateEventException) {
            return $this->apiHelper->getJsonResponse($response, [
                'status' => 'error',
                'details' => [
                    'Event with the same details exists. This is likely a duplicate event submission.'
                ],
            ], 409);
        }

        return $this->apiHelper->getJsonResponse($response, $handlerResponse, 201);
    }

    // This could be obviously refactored to use a validation library or separate validator class
    // But for the sake of simplicity and given the current scope, I implemented it this way
    private function getEventValidationErrors(array $data): array
    {
        $errors = [];

        if ($data['type'] === 'foul') {
            $errors = $this->getFoulEventValidationErrors($data);
        }

        if ($data['type'] === 'goal') {
            $errors = $this->getGoalEventValidationErrors($data);
        }

        return $errors;
    }

    private function getFoulEventValidationErrors(array $data): array
    {
        $errors = [];

        if (!isset($data['match_id']) || !is_string($data['match_id']) || trim($data['match_id']) === '') {
            $errors['match_id'] = 'Field "match_id" is required and must be a non-empty string';
        }

        if (!isset($data['team_at_fault_id']) || !is_string($data['team_at_fault_id']) || trim($data['team_at_fault_id']) === '') {
            $errors['team_at_fault_id'] = 'Field "team_at_fault_id" is required and must be a non-empty string';
        }

        if (!isset($data['player_at_fault']) || !is_string($data['player_at_fault']) || trim($data['player_at_fault']) === '') {
            $errors['player_at_fault'] = 'Field "player_at_fault" is required and must be a non-empty string';
        }

        if (!isset($data['minute']) || !is_integer($data['minute'])) {
            $errors['minute'] = 'Field "minute" must be an integer';
        }

        if (!isset($data['second']) || !is_integer($data['second'])) {
            $errors['second'] = 'Field "second" must be an integer';
        }

        if (!isset($data['timestamp']) || !is_integer($data['timestamp'])) {
            $errors['timestamp'] = 'Field "timestamp" must be an integer';
        }

        return $errors;
    }

    private function getGoalEventValidationErrors(array $data): array
    {
        $errors = [];

        if (!isset($data['match_id']) || !is_string($data['match_id']) || trim($data['match_id']) === '') {
            $errors['match_id'] = 'Field "match_id" is required and must be a non-empty string';
        }

        if (!isset($data['team_id']) || !is_string($data['team_id']) || trim($data['team_id']) === '') {
            $errors['team_id'] = 'Field "team_id" is required and must be a non-empty string';
        }

        if (!isset($data['scorer']) || !is_string($data['scorer']) || trim($data['scorer']) === '') {
            $errors['scorer'] = 'Field "scorer" is required and must be a non-empty string';
        }

        if (!isset($data['assisting_player']) || !is_string($data['assisting_player']) || trim($data['assisting_player']) === '') {
            $errors['assisting_player'] = 'Field "assisting_player" is required and must be a non-empty string';
        }

        if (isset($data['minute']) && !is_integer($data['minute'])) {
            $errors['minute'] = 'Field "minute" must be an integer';
        }

        if (isset($data['second']) && !is_integer($data['second'])) {
            $errors['second'] = 'Field "second" must be an integer';
        }

        return $errors;
    }
}
