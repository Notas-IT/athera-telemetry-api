<?php

namespace App\Entity;

use App\Repository\TelemetryRecordRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Vienas įrenginio AVL įrašas. Žinomi parametrai laikomi stulpeliuose,
 * kad užklausos ir indeksai būtų pigūs, visas gautas io objektas - JSONB stulpelyje.
 */
#[ORM\Entity(repositoryClass: TelemetryRecordRepository::class)]
#[ORM\Table(name: 'telemetry_record')]
#[ORM\Index(name: 'idx_telemetry_vehicle_time', columns: ['vehicle_id', 'recorded_at'])]
class TelemetryRecord
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT)]
    private ?string $id = null;

    #[ORM\ManyToOne(targetEntity: Vehicle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private Vehicle $vehicle;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE)]
    private \DateTimeImmutable $recordedAt;

    // Signed, degrees WGS84. DECIMAL, kad koordinatė būtų saugoma tiksliai, be float paklaidų
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private string $latitude;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 7)]
    private string $longitude;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $altitude = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $speed = null;

    #[ORM\Column(nullable: true)]
    private ?bool $ignition = null;

    #[ORM\Column(nullable: true)]
    private ?bool $movement = null;

    #[ORM\Column(type: Types::SMALLINT, nullable: true)]
    private ?int $gsmSignal = null;

    // Kaupiamasis odometras metrais (AVL 216)
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $odometer = null;

    // Kaupiamasis sunaudotas kuras mililitrais (AVL 86)
    #[ORM\Column(type: Types::BIGINT, nullable: true)]
    private ?string $fuelUsed = null;

    #[ORM\Column(type: Types::JSON, nullable: true, options: ['jsonb' => true])]
    private ?array $io = null;

    public function __construct(Vehicle $vehicle, \DateTimeImmutable $recordedAt, string $latitude, string $longitude)
    {
        $this->vehicle = $vehicle;
        $this->recordedAt = $recordedAt;
        $this->latitude = $latitude;
        $this->longitude = $longitude;
    }

    public function getId(): ?string
    {
        return $this->id;
    }

    public function getVehicle(): Vehicle
    {
        return $this->vehicle;
    }

    public function getRecordedAt(): \DateTimeImmutable
    {
        return $this->recordedAt;
    }

    public function getLatitude(): string
    {
        return $this->latitude;
    }

    public function getLongitude(): string
    {
        return $this->longitude;
    }

    public function getAltitude(): ?int
    {
        return $this->altitude;
    }

    public function setAltitude(?int $altitude): void
    {
        $this->altitude = $altitude;
    }

    public function getSpeed(): ?int
    {
        return $this->speed;
    }

    public function setSpeed(?int $speed): void
    {
        $this->speed = $speed;
    }

    public function getIgnition(): ?bool
    {
        return $this->ignition;
    }

    public function setIgnition(?bool $ignition): void
    {
        $this->ignition = $ignition;
    }

    public function getMovement(): ?bool
    {
        return $this->movement;
    }

    public function setMovement(?bool $movement): void
    {
        $this->movement = $movement;
    }

    public function getGsmSignal(): ?int
    {
        return $this->gsmSignal;
    }

    public function setGsmSignal(?int $gsmSignal): void
    {
        $this->gsmSignal = $gsmSignal;
    }

    public function getOdometer(): ?int
    {
        return $this->odometer !== null ? (int) $this->odometer : null;
    }

    public function setOdometer(?int $odometer): void
    {
        $this->odometer = $odometer !== null ? (string) $odometer : null;
    }

    public function getFuelUsed(): ?int
    {
        return $this->fuelUsed !== null ? (int) $this->fuelUsed : null;
    }

    public function setFuelUsed(?int $fuelUsed): void
    {
        $this->fuelUsed = $fuelUsed !== null ? (string) $fuelUsed : null;
    }

    public function getIo(): ?array
    {
        return $this->io;
    }

    public function setIo(?array $io): void
    {
        $this->io = $io;
    }
}
