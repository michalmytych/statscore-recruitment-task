<?php

namespace Tests\Api;

use Tests\Support\ApiTester;

class EventApiCest
{
    public function _before(ApiTester $I)
    {
        // Clean up storage files before each test
        $I->resetEventDatabase();
    }

    public function testFoulEvent(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'affected_player' => 'William Saliba',
            'player_at_fault' => 'Player at fault',
            'team_at_fault_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 45,
            'second' => 34,
            'timestamp' => time()
        ]);
        
        $I->seeResponseCodeIs(201);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'success',
            'message' => 'Event saved successfully'
        ]);
        $I->seeResponseJsonMatchesJsonPath('$.event.type', 'foul');
    }

    public function testDuplicatedEventRuleCheck(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');

        $payload = [
            'type' => 'foul',
            'affected_player' => 'William Saliba',
            'player_at_fault' => 'Player at fault',
            'team_at_fault_id' => 'arsenal',
            'match_id' => 'm1',
            'minute' => 45,
            'second' => 34,
            'timestamp' => time()
        ];
        
        // Create first event
        $I->sendPOST('/event', $payload);

        // Create another event with same data to trigger duplicate check
        $I->sendPOST('/event', $payload);        
        
        $I->seeResponseCodeIs(409);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'status' => 'error',
            'details' => [
                'Event with the same details exists. This is likely a duplicate event submission.'
            ]
        ]);
    }    

    public function testFoulEventWithoutRequiredFields(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'type' => 'foul',
            'player_at_fault' => 'William Saliba',
            'affected_player' => 'William Saliba',
            'minute' => 45,
            'second' => 34,
            'timestamp' => time()
            // Missing team_at_fault_id and match_id
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Validation failed',
            'details' => [
                'Field "match_id" is required and must be a non-empty string',
                'Field "team_at_fault_id" is required and must be a non-empty string'
            ]
        ]);
    }

    public function testInvalidJson(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', 'invalid json');
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Invalid JSON'
        ]);
    }

    public function testEventWithoutType(ApiTester $I)
    {
        $I->haveHttpHeader('Content-Type', 'application/json');
        $I->sendPOST('/event', [
            'player' => 'John Doe',
            'minute' => 23,
            'second' => 34
        ]);
        
        $I->seeResponseCodeIs(400);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson([
            'error' => 'Event type is required'
        ]);
    }
}
