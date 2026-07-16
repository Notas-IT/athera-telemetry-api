<?php

namespace App\Tests\Functional;

use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ReportEndpointTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $this->em->createQuery('DELETE FROM ' . TelemetryRecord::class)->execute();
        $this->em->createQuery('DELETE FROM ' . Vehicle::class)->execute();
    }

    public function testReturnsDistanceAndFuelForRange(): void
    {
        $vehicle = $this->vehicle('ABC123');
        // 100 000 km ir 5000 l intervalo pradžioje, +43.211 km ir +67.89 l pabaigoje
        $this->record($vehicle, '2026-06-19 08:00:00', 100000000, 5000000);
        $this->record($vehicle, '2026-06-19 12:00:00', 100021600, 5030000);
        $this->record($vehicle, '2026-06-19 16:00:00', 100043211, 5067890);
        $this->em->flush();

        $this->client->request('GET', '/api/v1/vehicles/ABC123/report?from=2026-06-19T00:00:00Z&to=2026-06-20T00:00:00Z');

        $this->assertResponseIsSuccessful();

        $response = $this->json();
        $this->assertSame('ABC123', $response['plate']);
        $this->assertSame(43.211, $response['distanceKm']);
        $this->assertSame(67.89, $response['fuelUsedLitres']);
    }

    public function testRecordsOutsideRangeAreIgnored(): void
    {
        $vehicle = $this->vehicle('ABC123');
        $this->record($vehicle, '2026-06-18 23:00:00', 99000000, 4900000); // prieš intervalą
        $this->record($vehicle, '2026-06-19 08:00:00', 100000000, 5000000);
        $this->record($vehicle, '2026-06-19 16:00:00', 100010000, 5001000);
        $this->record($vehicle, '2026-06-20 01:00:00', 105000000, 5100000); // po intervalo
        $this->em->flush();

        $this->client->request('GET', '/api/v1/vehicles/ABC123/report?from=2026-06-19T00:00:00Z&to=2026-06-20T00:00:00Z');

        $response = $this->json();
        // apvalios reikšmės JSON'e tampa int, todėl lyginam per cast
        $this->assertSame(10.0, (float) $response['distanceKm']);
        $this->assertSame(1.0, (float) $response['fuelUsedLitres']);
    }

    public function testPlateIsCaseInsensitive(): void
    {
        $vehicle = $this->vehicle('ABC123');
        $this->record($vehicle, '2026-06-19 08:00:00', 100000000, 5000000);
        $this->em->flush();

        $this->client->request('GET', '/api/v1/vehicles/abc123/report?from=2026-06-19T00:00:00Z&to=2026-06-20T00:00:00Z');

        $this->assertResponseIsSuccessful();
    }

    public function testUnknownPlateReturns404(): void
    {
        $this->client->request('GET', '/api/v1/vehicles/XXX999/report?from=2026-06-19T00:00:00Z&to=2026-06-20T00:00:00Z');

        $this->assertResponseStatusCodeSame(404);
    }

    public function testMissingParamsReturns422(): void
    {
        $this->vehicle('ABC123');
        $this->em->flush();

        $this->client->request('GET', '/api/v1/vehicles/ABC123/report');

        $this->assertResponseStatusCodeSame(422);
    }

    public function testFromAfterToReturns422(): void
    {
        $this->vehicle('ABC123');
        $this->em->flush();

        $this->client->request('GET', '/api/v1/vehicles/ABC123/report?from=2026-06-20T00:00:00Z&to=2026-06-19T00:00:00Z');

        $this->assertResponseStatusCodeSame(422);
    }

    private function vehicle(string $plate): Vehicle
    {
        $vehicle = new Vehicle('356307042441013');
        $vehicle->setPlate($plate);
        $this->em->persist($vehicle);

        return $vehicle;
    }

    private function record(Vehicle $vehicle, string $time, int $odometer, int $fuel): void
    {
        $record = new TelemetryRecord(
            $vehicle,
            new \DateTimeImmutable($time, new \DateTimeZone('UTC')),
            54.687157,
            25.279652,
        );
        $record->setOdometer($odometer);
        $record->setFuelUsed($fuel);

        $this->em->persist($record);
    }

    private function json(): array
    {
        return json_decode($this->client->getResponse()->getContent(), true);
    }
}
