<?php

namespace App\Tests\Unit\Dto;

use App\Dto\TelemetryPayload;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class TelemetryPayloadTest extends TestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        $this->validator = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator();
    }

    public function testValidPayloadPasses(): void
    {
        $payload = new TelemetryPayload();
        $payload->imei = '356307042441013';
        $payload->records = [['gnss' => [], 'io' => []]];

        $this->assertCount(0, $this->validator->validate($payload));
    }

    public function testImeiMustBe15Digits(): void
    {
        $payload = new TelemetryPayload();
        $payload->imei = '12345';
        $payload->records = [[]];

        $this->assertGreaterThan(0, count($this->validator->validate($payload)));
    }

    public function testImeiWithLettersFails(): void
    {
        $payload = new TelemetryPayload();
        $payload->imei = '35630704244101A';
        $payload->records = [[]];

        $this->assertGreaterThan(0, count($this->validator->validate($payload)));
    }

    public function testEmptyRecordsFails(): void
    {
        $payload = new TelemetryPayload();
        $payload->imei = '356307042441013';
        $payload->records = [];

        $this->assertGreaterThan(0, count($this->validator->validate($payload)));
    }
}
