<?php

namespace App\Tests\Unit\Report;

use App\Entity\Vehicle;
use App\Report\VehicleReportService;
use App\Repository\TelemetryRecordRepository;
use PHPUnit\Framework\TestCase;

class VehicleReportServiceTest extends TestCase
{
    private \DateTimeImmutable $from;
    private \DateTimeImmutable $to;

    protected function setUp(): void
    {
        $this->from = new \DateTimeImmutable('2026-06-01 00:00:00');
        $this->to = new \DateTimeImmutable('2026-06-30 23:59:59');
    }

    public function testCalculatesDistanceAndFuelFromCounterDeltas(): void
    {
        // odometras: 123 456 789 m -> 123 500 000 m, kuras: 4 500 000 ml -> 4 567 890 ml
        $service = $this->service([
            'odometer' => ['first' => 123456789, 'last' => 123500000],
            'fuelUsed' => ['first' => 4500000, 'last' => 4567890],
        ]);

        $report = $service->build($this->vehicle(), $this->from, $this->to);

        $this->assertSame(43.211, $report->distanceKm);
        $this->assertSame(67.89, $report->fuelUsedLitres);
        $this->assertSame('ABC123', $report->plate);
    }

    public function testNoDataInRangeGivesZeros(): void
    {
        $service = $this->service([
            'odometer' => ['first' => null, 'last' => null],
            'fuelUsed' => ['first' => null, 'last' => null],
        ]);

        $report = $service->build($this->vehicle(), $this->from, $this->to);

        $this->assertSame(0.0, $report->distanceKm);
        $this->assertSame(0.0, $report->fuelUsedLitres);
    }

    public function testSingleRecordGivesZeroDelta(): void
    {
        $service = $this->service([
            'odometer' => ['first' => 123456789, 'last' => 123456789],
            'fuelUsed' => ['first' => 4500000, 'last' => 4500000],
        ]);

        $report = $service->build($this->vehicle(), $this->from, $this->to);

        $this->assertSame(0.0, $report->distanceKm);
        $this->assertSame(0.0, $report->fuelUsedLitres);
    }

    public function testCounterResetIsTreatedAsZero(): void
    {
        // Paskutinė reikšmė mažesnė už pirmą - skaitiklis buvo perkrautas
        $service = $this->service([
            'odometer' => ['first' => 123456789, 'last' => 5000],
            'fuelUsed' => ['first' => 4500000, 'last' => 100],
        ]);

        $report = $service->build($this->vehicle(), $this->from, $this->to);

        $this->assertSame(0.0, $report->distanceKm);
        $this->assertSame(0.0, $report->fuelUsedLitres);
    }

    public function testFuelOnlyDataStillReported(): void
    {
        $service = $this->service([
            'odometer' => ['first' => null, 'last' => null],
            'fuelUsed' => ['first' => 4500000, 'last' => 4501500],
        ]);

        $report = $service->build($this->vehicle(), $this->from, $this->to);

        $this->assertSame(0.0, $report->distanceKm);
        $this->assertSame(1.5, $report->fuelUsedLitres);
    }

    /**
     * Pakiša repository stub'ą su fiksuotom skaitiklių reikšmėm.
     *
     * @param array<string, array{first: ?int, last: ?int}> $values
     */
    private function service(array $values): VehicleReportService
    {
        $repository = $this->createStub(TelemetryRecordRepository::class);

        $repository->method('counterValue')->willReturnCallback(
            function (Vehicle $vehicle, string $field, \DateTimeImmutable $from, \DateTimeImmutable $to, bool $last = false) use ($values): ?int {
                $position = $last ? 'last' : 'first';

                return $values[$field][$position];
            }
        );

        return new VehicleReportService($repository);
    }

    private function vehicle(): Vehicle
    {
        $vehicle = new Vehicle('356307042441013');
        $vehicle->setPlate('ABC123');

        return $vehicle;
    }
}
