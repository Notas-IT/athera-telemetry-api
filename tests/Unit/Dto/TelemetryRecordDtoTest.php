<?php

namespace App\Tests\Unit\Dto;

use App\Dto\GnssDto;
use App\Dto\TelemetryRecordDto;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TelemetryRecordDtoTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidRecordPasses(): void
    {
        $record = new TelemetryRecordDto();
        $record->gnss = $this->validGnss();
        $record->io = ['239' => 1, '216' => 123456789];

        $this->assertCount(0, $this->validator->validate($record));
    }

    public function testRecordWithoutGnssFails(): void
    {
        $record = new TelemetryRecordDto();

        $this->assertGreaterThan(0, count($this->validator->validate($record)));
    }

    public function testMissingTimestampFails(): void
    {
        $record = new TelemetryRecordDto();
        $record->gnss = $this->validGnss();
        $record->gnss->timestamp = null;

        $violations = $this->validator->validate($record);

        $this->assertGreaterThan(0, count($violations));
        $this->assertSame('gnss.timestamp', $violations[0]->getPropertyPath());
    }

    public function testLatitudeOutOfRangeFails(): void
    {
        $record = new TelemetryRecordDto();
        $record->gnss = $this->validGnss();
        $record->gnss->latitude = 95.0;

        $this->assertGreaterThan(0, count($this->validator->validate($record)));
    }

    public function testLongitudeOutOfRangeFails(): void
    {
        $record = new TelemetryRecordDto();
        $record->gnss = $this->validGnss();
        $record->gnss->longitude = -200.0;

        $this->assertGreaterThan(0, count($this->validator->validate($record)));
    }

    private function validGnss(): GnssDto
    {
        $gnss = new GnssDto();
        $gnss->timestamp = 1781849860.548;
        $gnss->latitude = 54.687157;
        $gnss->longitude = 25.279652;
        $gnss->altitude = 112;
        $gnss->speed = 67;

        return $gnss;
    }
}
