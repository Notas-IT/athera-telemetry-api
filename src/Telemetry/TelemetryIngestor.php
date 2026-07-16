<?php

namespace App\Telemetry;

use App\Dto\TelemetryPayload;
use App\Dto\TelemetryRecordDto;
use App\Entity\TelemetryRecord;
use App\Entity\Vehicle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Serializer\Exception\ExceptionInterface as SerializerException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Priima įrenginio siuntą: suranda arba sukuria vehicle pagal IMEI,
 * kiekvieną įrašą validuoja atskirai - blogi atmetami, geri saugomi.
 */
class TelemetryIngestor
{
    public function __construct(
        private EntityManagerInterface $em,
        private DenormalizerInterface $denormalizer,
        private ValidatorInterface $validator,
        private AvlMapper $avlMapper,
    ) {
    }

    public function ingest(TelemetryPayload $payload): IngestResult
    {
        $result = new IngestResult();
        $vehicle = $this->resolveVehicle($payload->imei);

        foreach (array_values($payload->records) as $index => $raw) {
            if (!is_array($raw)) {
                $result->reject($index, 'record must be an object');
                continue;
            }

            try {
                /** @var TelemetryRecordDto $dto */
                $dto = $this->denormalizer->denormalize($raw, TelemetryRecordDto::class);
            } catch (SerializerException|\TypeError) {
                $result->reject($index, 'record structure is not valid');
                continue;
            }

            $violations = $this->validator->validate($dto);
            if (count($violations) > 0) {
                $messages = [];
                foreach ($violations as $violation) {
                    $messages[] = $violation->getPropertyPath() . ': ' . $violation->getMessage();
                }
                $result->reject($index, ...$messages);
                continue;
            }

            $this->em->persist($this->buildRecord($vehicle, $dto));
            $result->accepted++;
        }

        $this->em->flush();

        return $result;
    }

    private function resolveVehicle(string $imei): Vehicle
    {
        $vehicle = $this->em->getRepository(Vehicle::class)->findOneBy(['imei' => $imei]);

        if ($vehicle === null) {
            $vehicle = new Vehicle($imei);
            $this->em->persist($vehicle);
        }

        return $vehicle;
    }

    private function buildRecord(Vehicle $vehicle, TelemetryRecordDto $dto): TelemetryRecord
    {
        $record = new TelemetryRecord(
            $vehicle,
            $this->toDateTime($dto->gnss->timestamp),
            $dto->gnss->latitude,
            $dto->gnss->longitude,
        );

        $record->setAltitude($dto->gnss->altitude);
        $record->setSpeed($dto->gnss->speed);

        $this->avlMapper->apply($record, $dto->io);

        // Numeris atnaujinamas kaskart kai atkeliauja - įrenginys galėjo būti perkeltas į kitą vilkiką
        $plate = $this->avlMapper->extractPlate($dto->io);
        if ($plate !== null) {
            $vehicle->setPlate($plate);
        }

        return $record;
    }

    // Epoch sekundės su trupmenine dalim -> UTC laikas su mikrosekundėm
    private function toDateTime(float $timestamp): \DateTimeImmutable
    {
        $dateTime = \DateTimeImmutable::createFromFormat('U.u', sprintf('%.6F', $timestamp));

        if ($dateTime === false) {
            throw new \InvalidArgumentException('invalid timestamp: ' . $timestamp);
        }

        return $dateTime;
    }
}
