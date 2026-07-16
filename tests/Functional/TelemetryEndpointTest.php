<?php

namespace App\Tests\Functional;

use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class TelemetryEndpointTest extends WebTestCase
{
    private const IMEI = '356307042441013';

    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        // Švari būsena kiekvienam testui
        $this->em->createQuery('DELETE FROM ' . TelemetryRecord::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Vehicle::class)->execute();
    }

    public function testStoresRecordsAndCreatesVehicle(): void
    {
        $this->post([
            'imei' => self::IMEI,
            'records' => [
                $this->record(1781849860.548, 123000000, 4500000, [231 => 'ABC', 232 => '123']),
                $this->record(1781849920.100, 123005000, 4502000),
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $response = $this->json();
        $this->assertSame(2, $response['accepted']);
        $this->assertSame(0, $response['rejected']);

        $vehicle = $this->em->getRepository(Vehicle::class)->findOneBy(['imei' => self::IMEI]);
        $this->assertNotNull($vehicle);
        $this->assertSame('ABC123', $vehicle->getPlate());
        $this->assertCount(2, $this->em->getRepository(TelemetryRecord::class)->findAll());
    }

    public function testInvalidRecordIsRejectedButOthersSaved(): void
    {
        $this->post([
            'imei' => self::IMEI,
            'records' => [
                $this->record(1781849860.548, 123000000, 4500000),
                ['gnss' => ['timestamp' => 1781849900.0, 'latitude' => 95.0, 'longitude' => 25.0]],
            ],
        ]);

        $this->assertResponseIsSuccessful();

        $response = $this->json();
        $this->assertSame(1, $response['accepted']);
        $this->assertSame(1, $response['rejected']);
        $this->assertArrayHasKey('1', $response['errors']);

        $this->assertCount(1, $this->em->getRepository(TelemetryRecord::class)->findAll());
    }

    public function testAllRecordsInvalidReturns422(): void
    {
        $this->post([
            'imei' => self::IMEI,
            'records' => [
                ['gnss' => ['latitude' => 54.7]],
            ],
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertCount(0, $this->em->getRepository(TelemetryRecord::class)->findAll());
    }

    public function testInvalidImeiReturns422(): void
    {
        $this->post([
            'imei' => '123',
            'records' => [$this->record(1781849860.548, 1, 1)],
        ]);

        $this->assertResponseStatusCodeSame(422);
    }

    public function testEmptyRecordsReturns422(): void
    {
        $this->post(['imei' => self::IMEI, 'records' => []]);

        $this->assertResponseStatusCodeSame(422);
    }

    private function post(array $payload): void
    {
        $this->client->request(
            'POST',
            '/api/v1/telemetry',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: json_encode($payload),
        );
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }

    private function record(float $timestamp, int $odometer, int $fuel, array $extraIo = []): array
    {
        return [
            'gnss' => [
                'timestamp' => $timestamp,
                'latitude' => 54.687157,
                'longitude' => 25.279652,
                'altitude' => 112,
                'speed' => 67,
            ],
            'io' => $extraIo + [239 => 1, 240 => 1, 21 => 4, 216 => $odometer, 86 => $fuel],
        ];
    }
}
