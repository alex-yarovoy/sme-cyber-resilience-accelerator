<?php

namespace App\Controller;

use App\Service\AuthMetricsService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MetricsController extends AbstractController
{
    #[Route('/api/metrics', name: 'api_metrics', methods: ['GET'])]
    public function metrics(AuthMetricsService $metrics): Response
    {
        return new Response($metrics->renderPrometheus(), Response::HTTP_OK, [
            'Content-Type' => 'text/plain; version=0.0.4; charset=utf-8',
        ]);
    }

    #[Route('/api/health', name: 'api_health', methods: ['GET'])]
    public function health(): Response
    {
        return $this->json(['status' => 'ok']);
    }
}
