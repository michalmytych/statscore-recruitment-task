<?php

declare(strict_types=1);

namespace MQ;

use App\Match\Service\MatchEventPublisherInterface as ServiceMatchEventPublisherInterface;
use App\Match\Service\PublishedEventDTO;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exception\AMQPTimeoutException;
use PhpAmqpLib\Message\AMQPMessage;

final class RabbitMQMatchEventPublisher implements ServiceMatchEventPublisherInterface
{
    // Same as in ws server
    private const EXCHANGE_NAME = 'match.events';
    private const EXCHANGE_TYPE = 'direct';
    private const ROUTING_KEY = 'match.event';

    private AMQPStreamConnection $connection;
    private AMQPChannel $channel;
    private bool $wasReturned = false;

    public function __construct() 
    {
        $this->connection = new AMQPStreamConnection(
            host: 'rabbitmq',
            port: 5672,
            user: 'guest', // Yes it's scary but it's for a demo app only
            password: 'guest',
            vhost: '/',
        );

        $this->channel = $this->connection->channel();

        $this->channel->exchange_declare(
            exchange: self::EXCHANGE_NAME,
            type: self::EXCHANGE_TYPE,
            passive: false,
            durable: true,
            auto_delete: false,
        );

        // Register if publisher confirmed getting message
        $this->channel->set_return_listener(function () {
            $this->wasReturned = true;
        });

        $this->channel->confirm_select();
    }

    public function publish(PublishedEventDTO $dto): void
    {
        $this->wasReturned = false;
        $payload = $this->buildPayload($dto);

        $message = new AMQPMessage(
            $payload,
            [
                'content_type' => 'application/json',
                'delivery_mode' => 2, // Recomendend mode
                'message_id' => $dto->idempotencyKey, // Simplest option
                'timestamp' => time(),
                'type' => $dto->eventType,
            ]
        );

        $this->channel->basic_publish(
            msg: $message,
            exchange: self::EXCHANGE_NAME,
            routing_key: self::ROUTING_KEY,
            mandatory: true,
        );

        try {
            $this->channel->wait_for_pending_acks_returns(5.0);
        } catch (AMQPTimeoutException $e) {
            throw new \RuntimeException('RabbitMQ publisher confirm timeout', 0, $e);
        }

        if ($this->wasReturned) {
            throw new \RuntimeException('RabbitMQ returned message as unroutable');
        }
    }

    public function close(): void
    {
        try {
            $this->channel->close();
        } finally {
            $this->connection->close();
        }
    }

    public function __destruct()
    {
        try {
            $this->close();
        } catch (\Throwable) {
        }
    }

    private function buildPayload(PublishedEventDTO $dto): string
    {
        return json_encode([
            'eventType' => $dto->eventType,
            'matchId' => $dto->matchId,
            'idempotencyKey' => $dto->idempotencyKey,
            'occurredAt' => (new \DateTimeImmutable())->format(DATE_ATOM),
        ], JSON_THROW_ON_ERROR);
    }
}