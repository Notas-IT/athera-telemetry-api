<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

class GnssDto
{
    // Unix epoch sekundės su trupmenine dalimi (pvz. 1781849860.548)
    #[Assert\NotNull]
    #[Assert\Positive]
    public ?float $timestamp = null;

    #[Assert\NotNull]
    #[Assert\Range(min: -90, max: 90)]
    public ?float $latitude = null;

    #[Assert\NotNull]
    #[Assert\Range(min: -180, max: 180)]
    public ?float $longitude = null;

    public ?int $altitude = null;

    #[Assert\Range(min: 0, max: 65535)]
    public ?int $speed = null;
}
