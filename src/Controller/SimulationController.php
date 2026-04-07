<?php

namespace App\Controller;

use App\DTO\Request\StartSimulationRequest;
use App\Service\SimulationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/simulations')]
class SimulationController extends AbstractController
{
    public function __construct(
        private readonly SimulationService $simulationService
    ) {}

    #[Route('', methods: ['POST'], name: 'create_simulation')]
    public function create(#[MapRequestPayload] StartSimulationRequest $request): JsonResponse
    {
        $simulation = $this->simulationService->start($request->topologyId);

        if(!$simulation) {
            return $this->json(['error' => 'Topology not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $simulation->getId(),
            'status' => $simulation->getStatus()->value,
            'started_at' => $simulation->getStartedAt()?->format('c'),
        ], Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['GET'], name: 'show_simulation')]
    public function show(int $id): JsonResponse
    {
        $simulation = $this->simulationService->findById($id);

        if(!$simulation) {
            return $this->json(['error' => 'Simulation not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'id' => $simulation->getId(),
            'status' => $simulation->getStatus()->value,
            'topologyId' => $simulation->getTopology()->getId(),
            'startedAt' => $simulation->getStartedAt()?->format('c'),
            'completedAt' => $simulation->getCompletedAt()?->format('c'),
            'eventCount' => $simulation->getEvents()->count()
        ]);
    }

    #[Route('/{id}/events', methods: ['GET'], name: 'simulation_events')]
    public function events(int $id): JsonResponse
    {
        $simulation = $this->simulationService->findById($id);

        if(!$simulation) {
            return $this->json(['error' => 'Simulation not found'], Response::HTTP_NOT_FOUND);
        }

        $events = $simulation->getEvents()->map(fn($event) => [
            'id' => $event->getId(),
            'sourceNodeId' => $event->getSourceNode()->getId(),
            'targetNodeId' => $event->getTargetNode()->getId(),
            'status' => $event->getStatus()->value,
            'latency' => $event->getLatency(),
            'createdAt' => $event->getCreatedAt()->format('c')
        ])->toArray();

        return $this->json($events);
    }
}
