<?php

namespace Tests;

use App\Common\EventInterface;
use App\EventHandler;
use App\Match\Event\FoulEvent;
use App\Match\Event\GoalEvent;
use App\Match\Projection\StatisticsProjection;
use App\Match\Repository\EventRepositoryInterface;
use App\Match\Service\MatchEventPublisherInterface;
use App\Match\VO\MatchEventTime;
use App\Match\Handler\FoulEventHandler;
use App\Match\Handler\GoalEventHandler;
use App\Match\Service\EventIdempotencyKeyFactory;
use Persistence\FileStorageEventRepository;
use Persistence\NullMatchEventPublisher;
use PHPUnit\Framework\TestCase;

class EventHandlerTest extends TestCase
{
    private string $eventsFile;
    private EventRepositoryInterface $eventRepository;
    private MatchEventPublisherInterface $publisher;
    private EventIdempotencyKeyFactory $idempotencyKeyFactory;

    protected function setUp(): void
    {
        $this->eventsFile = __DIR__ . '/../../storage/events.txt';
        @unlink($this->eventsFile);
        $this->eventRepository = new FileStorageEventRepository();
        $this->publisher = new NullMatchEventPublisher();
        $this->idempotencyKeyFactory = new EventIdempotencyKeyFactory();
    }

    protected function tearDown(): void
    {
        @unlink($this->eventsFile);
    }

    public function testHandleGoalEvent(): void
    {
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $event = new GoalEvent(
            matchId: 'm1',
            teamId: 'arsenal',
            scorerPlayer: 'John Doe',
            assistingPlayer: 'Jane Smith',
            eventTime: new MatchEventTime(
                occurenceMinutes: 23,
                occurenceSeconds: 34,
                timestamp: null
            )
        );

        $result = $handler->handleEvent($event);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Event saved successfully', $result['message']);
        $this->assertSame('goal', $result['event']['type']);
        $this->assertArrayHasKey('timestamp', $result['event']);
        $this->assertSame('arsenal', $result['event']['data']['team_id']);
    }

    public function testHandleUnsupportedEventType(): void
    {
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $event = new class implements EventInterface {
        };

        $result = $handler->handleEvent($event);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Event processing failed', $result['message']);
        $this->assertSame('Unsupported event type', $result['reason']);
    }

    public function testEventIsSavedToFile(): void
    {
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $handler->handleEvent(new GoalEvent(
            matchId: 'm1',
            teamId: 'arsenal',
            scorerPlayer: 'Jane Smith',
            assistingPlayer: 'John Doe',
            eventTime: new MatchEventTime(
                occurenceMinutes: 10,
                occurenceSeconds: 11,
                timestamp: null
            )
        ));

        $savedEvents = $this->eventRepository->findByMatchId('m1');

        $this->assertCount(1, $savedEvents);
        $this->assertSame('goal', $savedEvents[0]['type']);
    }

    public function testHandleFoulEventUpdatesStatistics(): void
    {
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $result = $handler->handleEvent(new FoulEvent(
            matchId: 'm1',
            teamAtFaultId: 'arsenal',
            playerAtFault: 'William Saliba',
            affectedPlayer: 'Gabriel Jesus',
            eventTime: new MatchEventTime(
                occurenceMinutes: 45,
                occurenceSeconds: 34,
                timestamp: 1710000000
            )
        ));

        $teamStats = $statisticsProjection->projectTeam(
            $this->eventRepository->findByMatchId('m1'),
            'm1',
            'arsenal'
        );

        $this->assertSame('success', $result['status']);
        $this->assertSame('foul', $result['event']['type']);
        $this->assertArrayHasKey('fouls', $teamStats);
        $this->assertSame(1, $teamStats['fouls']);
    }

    public function testHandleMultipleFoulEventsIncrementsStatistics(): void
    {
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher , $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $handler->handleEvent(new FoulEvent(
            matchId: 'match_1',
            teamAtFaultId: 'team_a',
            playerAtFault: 'John Doe',
            affectedPlayer: 'Jane Smith',
            eventTime: new MatchEventTime(
                occurenceMinutes: 15,
                occurenceSeconds: 34,
                timestamp: 1710000001
            )
        ));

        $handler->handleEvent(new FoulEvent(
            matchId: 'match_1',
            teamAtFaultId: 'team_a',
            playerAtFault: 'Jane Smith',
            affectedPlayer: 'John Doe',
            eventTime: new MatchEventTime(
                occurenceMinutes: 30,
                occurenceSeconds: 34,
                timestamp: 1710000002
            )
        ));

        $teamStats = $statisticsProjection->projectTeam(
            $this->eventRepository->findByMatchId('match_1'),
            'match_1',
            'team_a'
        );

        $this->assertSame(2, $teamStats['fouls']);
    }

    public function testHandleGoalEventUpdatesStatistics(): void
    {
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(
            new FoulEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
            new GoalEventHandler($this->eventRepository, $this->publisher, $this->idempotencyKeyFactory),
        );

        $handler->handleEvent(new GoalEvent(
            matchId: 'm2',
            teamId: 'barcelona',
            scorerPlayer: 'Robert Lewandowski',
            assistingPlayer: 'Lamine Yamal',
            eventTime: new MatchEventTime(
                occurenceMinutes: 67,
                occurenceSeconds: 12,
                timestamp: null
            )
        ));

        $teamStats = $statisticsProjection->projectTeam(
            $this->eventRepository->findByMatchId('m2'),
            'm2',
            'barcelona'
        );

        $this->assertSame(1, $teamStats['goals']);
    }
}
