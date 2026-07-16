<?php

namespace App\Controller;

use App\Dto\TelemetryPayload;
use App\Telemetry\TelemetryIngestor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

class TelemetryController extends AbstractController
{
    /**
     * Priima įrenginio AVL įrašų siuntą. Payload'o struktūrą validuoja
     * MapRequestPayload (400/422), o įrašai tikrinami po vieną - blogi
     * grąžinami errors sąraše, geri išsaugomi.
     */
    #[Route('/api/v1/telemetry', name: 'telemetry_ingest', methods: ['POST'])]
    public function ingest(
        #[MapRequestPayload] TelemetryPayload $payload,
        TelemetryIngestor $ingestor,
    ): JsonResponse {
        $result = $ingestor->ingest($payload);

        $status = $result->accepted > 0
            ? Response::HTTP_OK
            : Response::HTTP_UNPROCESSABLE_ENTITY;

        return $this->json([
            'accepted' => $result->accepted,
            'rejected' => $result->getRejected(),
            'errors' => $result->errors,
        ], $status);
    }
}
