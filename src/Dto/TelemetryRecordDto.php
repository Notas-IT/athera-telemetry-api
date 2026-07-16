<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class TelemetryRecordDto
{
    #[Assert\NotNull]
    #[Assert\Valid]
    public ?GnssDto $gnss = null;

    // AVL parametrai "id" => reikšmė, saugomi kaip gauti iš įrenginio
    #[Assert\Type('array')]
    public array $io = [];
}
