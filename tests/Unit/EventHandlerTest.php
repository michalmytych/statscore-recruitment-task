<?php

namespace Tests;

use App\Common\EventInterface;
use App\EventHandler;
use App\FileStorage;
use App\Match\Event\FoulEvent;
use App\Match\Event\GoalEvent;
use App\Match\Projection\StatisticsProjection;
use App\Match\VO\MatchEventTime;
use App\Match\Handler\FoulEventHandler;
use App\Match\Handler\GoalEventHandler;
use PHPUnit\Framework\TestCase;

class EventHandlerTest extends TestCase
{
    private string $eventsFile;

    protected function setUp(): void
    {
        $this->eventsFile = __DIR__ . '/../../storage/events.txt';

        @unlink($this->eventsFile);
    }

    protected function tearDown(): void
    {
        @unlink($this->eventsFile);
    }

    public function testHandleGoalEvent(): void
    {
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

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
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

        $event = new class implements EventInterface {
        };

        $result = $handler->handleEvent($event);

        $this->assertSame('error', $result['status']);
        $this->assertSame('Event processing failed', $result['message']);
        $this->assertSame('Unsupported event type', $result['reason']);
    }

    public function testEventIsSavedToFile(): void
    {
        $storage = new FileStorage($this->eventsFile);
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

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

        $savedEvents = $storage->getAll();

        $this->assertCount(1, $savedEvents);
        $this->assertSame('goal', $savedEvents[0]['type']);
    }

    public function testHandleFoulEventUpdatesStatistics(): void
    {
        $storage = new FileStorage($this->eventsFile);
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

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

        $teamStats = $statisticsProjection->projectTeam($storage->getAll(), 'm1', 'arsenal');

        $this->assertSame('success', $result['status']);
        $this->assertSame('foul', $result['event']['type']);
        $this->assertArrayHasKey('fouls', $teamStats);
        $this->assertSame(1, $teamStats['fouls']);
    }

    public function testHandleMultipleFoulEventsIncrementsStatistics(): void
    {
        $storage = new FileStorage($this->eventsFile);
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

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

        $teamStats = $statisticsProjection->projectTeam($storage->getAll(), 'match_1', 'team_a');

        $this->assertSame(2, $teamStats['fouls']);
    }

    public function testHandleGoalEventUpdatesStatistics(): void
    {
        $storage = new FileStorage($this->eventsFile);
        $statisticsProjection = new StatisticsProjection();
        $handler = new EventHandler(new FoulEventHandler(), new GoalEventHandler());

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

        $teamStats = $statisticsProjection->projectTeam($storage->getAll(), 'm2', 'barcelona');

        $this->assertSame(1, $teamStats['goals']);
    }
}
