<?php

namespace App\Tests\Unit\Telemetry;

use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use App\Telemetry\AvlMapper;
use PHPUnit\Framework\TestCase;

class AvlMapperTest extends TestCase
{
    private AvlMapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new AvlMapper();
    }

    public function testMapsKnownParameters(): void
    {
        $record = $this->record();
        $io = [239 => 1, 240 => 0, 21 => 4, 216 => 123456789, 86 => 4567890];

        $this->mapper->apply($record, $io);

        $this->assertTrue($record->getIgnition());
        $this->assertFalse($record->getMovement());
        $this->assertSame(4, $record->getGsmSignal());
        $this->assertSame(123456789, $record->getOdometer());
        $this->assertSame(4567890, $record->getFuelUsed());
        $this->assertSame($io, $record->getIo());
    }

    public function testMissingParametersStayNull(): void
    {
        $record = $this->record();

        $this->mapper->apply($record, []);

        $this->assertNull($record->getIgnition());
        $this->assertNull($record->getOdometer());
        $this->assertNull($record->getFuelUsed());
        $this->assertNull($record->getIo());
    }

    public function testIgnoresGarbageValues(): void
    {
        $record = $this->record();

        $this->mapper->apply($record, [216 => 'abc', 239 => 'x', 21 => null]);

        $this->assertNull($record->getOdometer());
        $this->assertNull($record->getIgnition());
        $this->assertNull($record->getGsmSignal());
    }

    public function testSpeedFallbackFromIo(): void
    {
        $record = $this->record();

        $this->mapper->apply($record, [24 => 55]);

        $this->assertSame(55, $record->getSpeed());
    }

    public function testGnssSpeedIsNotOverwritten(): void
    {
        $record = $this->record();
        $record->setSpeed(67);

        $this->mapper->apply($record, [24 => 55]);

        $this->assertSame(67, $record->getSpeed());
    }

    public function testPlateFromTwoParts(): void
    {
        $plate = $this->mapper->extractPlate([231 => 'abc', 232 => '123']);

        $this->assertSame('ABC123', $plate);
    }

    public function testPlateFirstPartOnly(): void
    {
        $plate = $this->mapper->extractPlate([231 => 'ABC123']);

        $this->assertSame('ABC123', $plate);
    }

    public function testPlateMissingReturnsNull(): void
    {
        $this->assertNull($this->mapper->extractPlate([]));
        $this->assertNull($this->mapper->extractPlate([232 => '123']));
        $this->assertNull($this->mapper->extractPlate([231 => '   ']));
    }

    private function record(): TelemetryRecord
    {
        return new TelemetryRecord(
            new Vehicle('356307042441013'),
            new \DateTimeImmutable('2026-06-19 12:00:00'),
            54.687157,
            25.279652,
        );
    }
}
