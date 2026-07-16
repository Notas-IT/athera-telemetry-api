<?php

namespace App\Controller;

use App\Dto\ReportQuery;
use App\Entity\Vehicle;
use App\Report\VehicleReportService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryString;
use Symfony\Component\Routing\Attribute\Route;

class ReportController extends AbstractController
{
    /**
     * Grąžina vilkiko nuvažiuotą atstumą (km) ir sunaudotą kurą (l)
     * per nurodytą laiko intervalą.
     */
    #[Route('/api/v1/vehicles/{plate}/report', name: 'vehicle_report', methods: ['GET'])]
    public function report(
        string $plate,
        #[MapQueryString(validationFailedStatusCode: Response::HTTP_UNPROCESSABLE_ENTITY)] ReportQuery $query,
        EntityManagerInterface $em,
        VehicleReportService $reports,
    ): JsonResponse {
        if ($query->from >= $query->to) {
            return $this->json(['error' => 'from must be earlier than to'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $vehicle = $em->getRepository(Vehicle::class)->findOneBy(['plate' => strtoupper($plate)]);
        if ($vehicle === null) {
            return $this->json(['error' => 'vehicle not found'], Response::HTTP_NOT_FOUND);
        }

        $report = $reports->build($vehicle, $query->from, $query->to);

        return $this->json([
            'plate' => $report->plate,
            'from' => $report->from->format(DATE_ATOM),
            'to' => $report->to->format(DATE_ATOM),
            'distanceKm' => $report->distanceKm,
            'fuelUsedLitres' => $report->fuelUsedLitres,
        ]);
    }
}
