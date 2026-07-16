<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * GET report užklausos parametrai (?from=...&to=... ISO 8601 formatu).
 */
class ReportQuery
{
    #[Assert\NotNull(message: 'from parameter is required')]
    public ?\DateTimeImmutable $from = null;

    #[Assert\NotNull(message: 'to parameter is required')]
    public ?\DateTimeImmutable $to = null;
}
