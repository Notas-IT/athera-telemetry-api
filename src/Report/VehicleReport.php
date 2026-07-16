<?php

namespace App\Report;

/**
 * Ataskaitos rezultatas: nuvažiuotas atstumas (km) ir sunaudotas kuras (l)
 * per užklaustą laiko intervalą.
 */
class VehicleReport
{
    public function __construct(
        public readonly ?string $plate,
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly float $distanceKm,
        public readonly float $fuelUsedLitres,
    ) {
    }
}
