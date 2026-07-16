<?php

namespace App\Repository;

use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TelemetryRecordRepository extends ServiceEntityRepository
{
    private const COUNTER_FIELDS = ['odometer', 'fuelUsed'];

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TelemetryRecord::class);
    }

    /**
     * Pirma arba paskutinė ne-null kaupiamojo skaitiklio reikšmė intervale.
     * Užklausa naudoja composite indeksą (vehicle_id, recorded_at) - ORDER BY + LIMIT 1
     * neskaito viso intervalo, todėl veikia ir ant labai didelių lentelių.
     */
    public function counterValue(
        Vehicle $vehicle,
        string $field,
        \DateTimeImmutable $from,
        \DateTimeImmutable $to,
        bool $last = false,
    ): ?int {
        if (!in_array($field, self::COUNTER_FIELDS, true)) {
            throw new \InvalidArgumentException('unsupported counter field: ' . $field);
        }

        $row = $this->createQueryBuilder('r')
            ->select('r.' . $field . ' AS value')
            ->where('r.vehicle = :vehicle')
            ->andWhere('r.recordedAt BETWEEN :from AND :to')
            ->andWhere('r.' . $field . ' IS NOT NULL')
            ->orderBy('r.recordedAt', $last ? 'DESC' : 'ASC')
            ->setMaxResults(1)
            ->setParameter('vehicle', $vehicle)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()
            ->getOneOrNullResult();

        return $row === null ? null : (int) $row['value'];
    }
}
