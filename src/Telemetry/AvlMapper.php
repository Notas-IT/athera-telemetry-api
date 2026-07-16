<?php

namespace App\Telemetry;

use App\Constants\AvlParameters;
use App\Entity\TelemetryRecord;

/**
 * Ištraukia žinomus AVL parametrus iš io objekto į įrašo stulpelius.
 * Visas gautas io objektas papildomai saugomas kaip gautas (JSONB),
 * todėl nežinomi parametrai neprarandami.
 */
class AvlMapper
{
    public function apply(TelemetryRecord $record, array $io): void
    {
        $record->setIo($io ?: null);

        $record->setIgnition($this->boolValue($io[AvlParameters::IGNITION] ?? null));
        $record->setMovement($this->boolValue($io[AvlParameters::MOVEMENT] ?? null));
        $record->setGsmSignal($this->intValue($io[AvlParameters::GSM_SIGNAL] ?? null));
        $record->setOdometer($this->intValue($io[AvlParameters::TOTAL_ODOMETER] ?? null));
        $record->setFuelUsed($this->intValue($io[AvlParameters::ENGINE_TOTAL_FUEL_USED] ?? null));

        // speed paprastai ateina gnss dalyje, bet FMC650 jį gali siųsti ir kaip AVL 24
        if ($record->getSpeed() === null) {
            $record->setSpeed($this->intValue($io[AvlParameters::SPEED] ?? null));
        }
    }

    // Valst. numeris siunčiamas dviem dalim (AVL 231 + 232) ir ne kiekviename įraše
    public function extractPlate(array $io): ?string
    {
        $part1 = $io[AvlParameters::PLATE_PART_1] ?? null;
        $part2 = $io[AvlParameters::PLATE_PART_2] ?? null;

        if (!is_string($part1) || trim($part1) === '') {
            return null;
        }

        $plate = trim($part1) . (is_string($part2) ? trim($part2) : '');

        return strtoupper($plate);
    }

    private function boolValue(mixed $value): ?bool
    {
        if (!is_numeric($value)) {
            return null;
        }

        return (int) $value === 1;
    }

    private function intValue(mixed $value): ?int
    {
        return is_numeric($value) ? (int) $value : null;
    }
}
