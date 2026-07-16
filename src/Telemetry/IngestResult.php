<?php

namespace App\Telemetry;

/**
 * Vienos siuntos apdorojimo rezultatas: kiek įrašų priimta
 * ir kurie atmesti su klaidų priežastim.
 */
class IngestResult
{
    public int $accepted = 0;

    /** @var array<int, string[]> record indeksas => klaidos */
    public array $errors = [];

    public function reject(int $index, string ...$messages): void
    {
        $this->errors[$index] = $messages;
    }

    public function getRejected(): int
    {
        return count($this->errors);
    }
}
