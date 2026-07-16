<?php

namespace App\Tests\Unit\Telemetry;

use App\Dto\TelemetryPayload;
use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use App\Telemetry\AvlMapper;
use App\Telemetry\TelemetryIngestor;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validation;

class TelemetryIngestorTest extends TestCase
{
    private const IMEI = '356307042441013';

    private $em;
    private $repository;
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->persisted = [];
        $this->repository = $this->createStub(EntityRepository::class);

        $this->em = $this->createStub(EntityManagerInterface::class);
        $this->em->method('getRepository')->willReturn($this->repository);
        $this->em->method('persist')->willReturnCallback(function ($entity) {
            $this->persisted[] = $entity;
        });
    }

    public function testAcceptsValidAndRejectsInvalidRecords(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);

        $payload = $this->payload([
            $this->validRecord(),
            ['gnss' => ['timestamp' => 1781849860.5, 'latitude' => 95.0, 'longitude' => 25.0]],
            $this->validRecord(),
        ]);

        $result = $this->ingestor()->ingest($payload);

        $this->assertSame(2, $result->accepted);
        $this->assertSame(1, $result->getRejected());
        $this->assertArrayHasKey(1, $result->errors);

        $records = array_filter($this->persisted, fn ($e) => $e instanceof TelemetryRecord);
        $this->assertCount(2, $records);
    }

    public function testCreatesVehicleWhenImeiIsNew(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);

        $this->ingestor()->ingest($this->payload([$this->validRecord()]));

        $vehicles = array_filter($this->persisted, fn ($e) => $e instanceof Vehicle);
        $this->assertCount(1, $vehicles);
    }

    public function testReusesExistingVehicleAndUpdatesPlate(): void
    {
        $existing = new Vehicle(self::IMEI);
        $this->repository->method('findOneBy')->willReturn($existing);

        $record = $this->validRecord();
        $record['io'][231] = 'ABC';
        $record['io'][232] = '123';

        $this->ingestor()->ingest($this->payload([$record]));

        $this->assertSame('ABC123', $existing->getPlate());
        $this->assertCount(0, array_filter($this->persisted, fn ($e) => $e instanceof Vehicle));
    }

    public function testRecordThatIsNotAnObjectIsRejected(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);

        $result = $this->ingestor()->ingest($this->payload(['garbage']));

        $this->assertSame(0, $result->accepted);
        $this->assertSame(1, $result->getRejected());
    }

    public function testRecordTimestampIsConvertedToUtc(): void
    {
        $this->repository->method('findOneBy')->willReturn(null);

        $this->ingestor()->ingest($this->payload([$this->validRecord()]));

        $records = array_values(array_filter($this->persisted, fn ($e) => $e instanceof TelemetryRecord));
        $this->assertSame('2026-06-19 06:17:40.548000', $records[0]->getRecordedAt()->format('Y-m-d H:i:s.u'));
    }

    private function ingestor(): TelemetryIngestor
    {
        $extractor = new PropertyInfoExtractor([], [new ReflectionExtractor()]);
        $serializer = new Serializer([new ObjectNormalizer(null, null, null, $extractor), new ArrayDenormalizer()]);
        $validator = Validation::createValidatorBuilder()->enableAttributeMapping()->getValidator();

        return new TelemetryIngestor($this->em, $serializer, $validator, new AvlMapper());
    }

    private function payload(array $records): TelemetryPayload
    {
        $payload = new TelemetryPayload();
        $payload->imei = self::IMEI;
        $payload->records = $records;

        return $payload;
    }

    private function validRecord(): array
    {
        return [
            'gnss' => [
                'timestamp' => 1781849860.548,
                'latitude' => 54.687157,
                'longitude' => 25.279652,
                'altitude' => 112,
                'speed' => 67,
            ],
            'io' => [239 => 1, 240 => 1, 21 => 4, 216 => 123456789, 86 => 4567890],
        ];
    }
}
