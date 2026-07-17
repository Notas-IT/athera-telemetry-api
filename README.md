# Telemetry API

Telematics data ingestion and reporting API. Receives AVL records from Teltonika FMC650
devices, stores them in PostgreSQL and calculates distance travelled and fuel consumed
for a vehicle over a time range.

Built with PHP 8.4 / Symfony 7.4.

## Requirements

- Docker with Compose

Everything else (PHP, Composer, PostgreSQL) runs in containers.

## Setup

```bash
docker compose up -d --build
docker compose exec php composer install
docker compose exec php php bin/console doctrine:migrations:migrate -n
```

API runs at http://localhost:8080.

## Tests

One time setup for the test database:

```bash
docker compose exec php php bin/console doctrine:database:create --env=test
docker compose exec php php bin/console doctrine:migrations:migrate -n --env=test
```

Run tests:

```bash
docker compose exec php php bin/phpunit                     # all
docker compose exec php php bin/phpunit --testsuite unit
docker compose exec php php bin/phpunit --testsuite functional
```

## Endpoints

Swagger UI: http://localhost:8080/api/doc

### POST /api/v1/telemetry

Accepts a batch of records from one device. Records are validated one by one -
invalid records are rejected, valid ones are stored.

```json
{
  "imei": "356307042441013",
  "records": [
    {
      "gnss": {
        "timestamp": 1781849860.548,
        "latitude": 54.687157,
        "longitude": 25.279652,
        "altitude": 112,
        "speed": 67
      },
      "io": {
        "239": 1,
        "240": 1,
        "21": 4,
        "216": 123456789,
        "86": 4567890,
        "231": "ABC",
        "232": "123"
      }
    }
  ]
}
```

`io` holds AVL parameters as `"id": value`. Known ids: 216 odometer (m), 86 fuel used (ml),
231/232 registration number parts, 239 ignition, 240 movement, 21 gsm signal, 24 speed.
Unknown parameters are stored as received.

A later batch from the same device with two records, carrying the registration
number (231+232) and all known parameters:

```json
{
  "imei": "356307042441013",
  "records": [
    {
      "gnss": {
        "timestamp": 1781849920.000,
        "latitude": 54.687601,
        "longitude": 25.280114,
        "altitude": 114,
        "speed": 52
      },
      "io": {
        "239": 1,
        "240": 1,
        "21": 5,
        "24": 52,
        "216": 123457921,
        "86": 4568120,
        "231": "ABC",
        "232": "123"
      }
    },
    {
      "gnss": {
        "timestamp": 1781849980.000,
        "latitude": 54.688204,
        "longitude": 25.280893,
        "altitude": 117,
        "speed": 64
      },
      "io": {
        "239": 1,
        "240": 1,
        "21": 5,
        "24": 64,
        "216": 123459004,
        "86": 4568390,
        "231": "ABC",
        "232": "123"
      }
    }
  ]
}
```

Response `200`:

```json
{"accepted": 1, "rejected": 0, "errors": []}
```

If some records are invalid, their indexes and reasons are returned in `errors`.
`422` - invalid imei, empty records or the whole batch rejected. `400` - malformed JSON.

### GET /api/v1/vehicles/{plate}/report?from=...&to=...

Distance (km) and fuel consumed (litres) for a vehicle over a period.
`from`/`to` are ISO 8601 datetimes.

```bash
curl "http://localhost:8080/api/v1/vehicles/ABC123/report?from=2026-06-19T00:00:00Z&to=2026-06-20T00:00:00Z"
```

Response `200`:

```json
{
  "plate": "ABC123",
  "from": "2026-06-19T00:00:00+00:00",
  "to": "2026-06-20T00:00:00+00:00",
  "distanceKm": 43.211,
  "fuelUsedLitres": 67.89
}
```

`404` - unknown plate. `422` - missing/invalid `from`/`to`.

## Notes

- Vehicle is identified by device IMEI. Registration number arrives as AVL 231+232
  and is not present in every record - it is attached to the vehicle when it shows up.
- Distance and fuel are calculated as counter deltas (last - first value in range),
  not from GPS coordinates.
- Records table is indexed by (vehicle_id, recorded_at). In production it would be
  partitioned by time to handle billions of rows.
