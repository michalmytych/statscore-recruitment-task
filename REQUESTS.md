# Requests

Examples of `curl` commands for testing the current API.

The realtime client demo is available at:

```bash
http://localhost:8000/demo
```

## POST /event

Endpoint for saving match events.

### Foul event

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "foul",
    "match_id": "m1",
    "team_at_fault_id": "arsenal",
    "player_at_fault": "William Saliba",
    "affected_player": "Gabriel Jesus",
    "minute": 45,
    "second": 34,
    "timestamp": 1710000000
  }'
```

Example response:

```json
{
  "status": "success",
  "message": "Event saved successfully",
  "event": {
    "type": "foul",
    "timestamp": 1710000000,
    "idempotency_key": "foul-m1-45-34",
    "data": {
      "match_id": "m1",
      "team_id": "arsenal",
      "player_at_fault": "William Saliba",
      "affected_player": "Gabriel Jesus",
      "second": 34,
      "minute": 45
    }
  }
}
```

### Goal event

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "goal",
    "match_id": "m1",
    "team_id": "arsenal",
    "scorer": "Bukayo Saka",
    "assisting_player": "Martin Odegaard",
    "minute": 12,
    "second": 8
  }'
```

Example response:

```json
{
  "status": "success",
  "message": "Event saved successfully",
  "event": {
    "type": "goal",
    "timestamp": null,
    "idempotency_key": "goal-m1-12-08",
    "data": {
      "match_id": "m1",
      "team_id": "arsenal",
      "scorer": "Bukayo Saka",
      "assisting_player": "Martin Odegaard",
      "second": 8,
      "minute": 12
    }
  }
}
```

### Missing type

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "match_id": "m1",
    "minute": 23,
    "second": 34
  }'
```

Expected response:

```json
{
  "error": "Event type is required"
}
```

### Invalid JSON

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d 'invalid json'
```

Expected response:

```json
{
  "error": "Invalid JSON"
}
```

### Foul event without required fields

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "foul",
    "player_at_fault": "William Saliba",
    "affected_player": "Gabriel Jesus",
    "minute": 45,
    "second": 34,
    "timestamp": 1710000000
  }'
```

Expected response:

```json
{
  "error": "Validation failed",
  "details": [
    "Field \"match_id\" is required and must be a non-empty string",
    "Field \"team_at_fault_id\" is required and must be a non-empty string"
  ]
}
```

### Unsupported event type

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "corner",
    "match_id": "m1",
    "minute": 20,
    "second": 10
  }'
```

Expected response:

```json
{
  "error": "Unsupported event type: corner"
}
```

### Duplicate event

Really basic idempotency check. Duplicated event results in `409` conflict response (payload is the same as first example of foul event).

```bash
curl -i -X POST http://localhost:8000/event \
  -H 'Content-Type: application/json' \
  -d '{
    "type": "foul",
    "match_id": "m1",
    "team_at_fault_id": "arsenal",
    "player_at_fault": "William Saliba",
    "affected_player": "Gabriel Jesus",
    "minute": 45,
    "second": 34,
    "timestamp": 1710000000
  }'
```

Expected response for the second identical request:

```json
{
  "status": "error",
  "details": [
    "Event with the same details exists. This is likely a duplicate event submission."
  ]
}
```

## GET /statistics

This endpoint returns statistics calculated from saved events.

### Team statistics for a match

```bash
curl -i "http://localhost:8000/statistics?match_id=m1&team_id=arsenal"
```

Example response:

```json
{
  "match_id": "m1",
  "team_id": "arsenal",
  "statistics": {
    "fouls": 2,
    "goals": 1
  }
}
```

### Statistics for all teams in a match

```bash
curl -i "http://localhost:8000/statistics?match_id=m1"
```

Example response:

```json
{
  "match_id": "m1",
  "statistics": {
    "arsenal": {
      "fouls": 2,
      "goals": 1
    },
    "liverpool": {
      "fouls": 1
    }
  }
}
```

### Missing match_id

```bash
curl -i "http://localhost:8000/statistics"
```

Expected response:

```json
{
  "error": "match_id is required"
}
```
