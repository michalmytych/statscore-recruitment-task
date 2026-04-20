<?php

namespace Tests\Api;

use Tests\Support\ApiTester;

class StatisticsApiCest
{
    public function _before(ApiTester $I)
    {
        // Clean up storage files before each test
        $I->resetEventDatabase();
    }

    public function testGetTeamStatistics(ApiTester $I)
    {
        // First, create some foul events
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'affected_player' => 'Robert lewnandowski',
            'player_at_fault' => 'William Saliba',
            'team_at_fault_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 15,
            'second' => 34,
            'timestamp' => time(),
        ]);
        $I->seeResponseCodeIs(201);
        
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'affected_player' => 'Paul Anka',
            'player_at_fault' => 'Gabriel Jesus',
            'team_at_fault_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 30,
            'second' => 33,
            'timestamp' => time(),
        ]);
        $I->seeResponseCodeIs(201);
        
        // Now get team statistics
        $I->sendGET('/statistics?match_id=m1&team_id=arsenal');
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'match_id' => 'm1',
            'team_id' => 'arsenal',
            'statistics' => [
                'fouls' => 2
            ]
        ]);
    }

    public function testGetMatchStatistics(ApiTester $I)
    {
        // Create foul events for different teams
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'affected_player' => 'Bukayo Saka',
            'player_at_fault' => 'William Saliba',
            'team_at_fault_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 15,
            'second' => 34,
            'timestamp' => time(),
        ]);
        $I->seeResponseCodeIs(201);
        
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'affected_player' => 'Mohamed Salah',
            'player_at_fault' => 'Virgil van Dijk',
            'team_at_fault_id' => 'liverpool',
            'match_id' => 'm1',
            'minute' => 30,
            'second' => 33,
            'timestamp' => time(),
        ]);
        $I->seeResponseCodeIs(201);
        
        // Get all match statistics
        $I->sendGET('/statistics?match_id=m1');
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'match_id' => 'm1',
            'statistics' => [
                'arsenal' => [
                    'fouls' => 1
                ],
                'liverpool' => [
                    'fouls' => 1
                ]
            ]
        ]);
    }

    public function testGetStatisticsWithoutMatchId(ApiTester $I)
    {
        $I->sendGET('/statistics');
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'match_id is required'
        ]);
    }

    public function testGetStatisticsForNonExistentTeam(ApiTester $I)
    {
        $I->sendGET('/statistics?match_id=m1&team_id=nonexistent');
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'match_id' => 'm1',
            'team_id' => 'nonexistent',
            'statistics' => []
        ]);
    }

    public function testGetStatisticsForNonExistentMatch(ApiTester $I)
    {
        $I->sendGET('/statistics?match_id=nonexistent');
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'match_id' => 'nonexistent',
            'statistics' => []
        ]);
    }
}
