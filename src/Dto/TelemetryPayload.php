<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Įrenginio siunčiamo POST payload'o kontraktas.
 * Įrašai validuojami po vieną atskirai, todėl čia laikomi kaip neapdoroti masyvai.
 */
class TelemetryPayload
{
    #[Assert\NotBlank]
    #[Assert\Regex(pattern: '/^\d{15}$/', message: 'imei must be 15 digits')]
    public ?string $imei = null;

    #[Assert\NotBlank(message: 'records must not be empty')]
    #[Assert\Type('array')]
    public array $records = [];
}
