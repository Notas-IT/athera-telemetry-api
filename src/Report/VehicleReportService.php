<?php

namespace App\Report;

use App\Entity\Vehicle;
use App\Repository\TelemetryRecordRepository;

/**
 * Skaičiuoja atstumą ir kurą iš kaupiamųjų skaitiklių deltų:
 * paskutinė reikšmė intervale minus pirma. Patikimiau nei sumuoti GPS
 * atstumus tarp taškų, nes GPS triukšmas kaupia paklaidą.
 */
class VehicleReportService
{
    public function __construct(private TelemetryRecordRepository $records)
    {
    }

    public function build(Vehicle $vehicle, \DateTimeImmutable $from, \DateTimeImmutable $to): VehicleReport
    {
        $distanceMeters = $this->counterDelta($vehicle, 'odometer', $from, $to);
        $fuelMillilitres = $this->counterDelta($vehicle, 'fuelUsed', $from, $to);

        return new VehicleReport(
            $vehicle->getPlate(),
            $from,
            $to,
            round($distanceMeters / 1000, 3),
            round($fuelMillilitres / 1000, 3),
        );
    }

    // Neigiama delta (skaitiklio reset, įrenginio mainai) traktuojama kaip 0 - geriau
    // parodyti mažiau, nei išgalvotą milžinišką reikšmę
    private function counterDelta(Vehicle $vehicle, string $field, \DateTimeImmutable $from, \DateTimeImmutable $to): int
    {
        $first = $this->records->counterValue($vehicle, $field, $from, $to);
        $last = $this->records->counterValue($vehicle, $field, $from, $to, last: true);

        if ($first === null || $last === null) {
            return 0;
        }

        return max(0, $last - $first);
    }
}
