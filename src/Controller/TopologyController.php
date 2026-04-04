<?php

namespace App\Controller;

use App\DTO\Request\SaveTopologyRequest;
use App\DTO\Response\TopologyListResponse;
use App\DTO\Response\TopologyResponse;
use App\Service\TopologyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/topologies')]
final class TopologyController extends AbstractController
{
    public function __construct(
        private readonly TopologyService $topologyService,
    ) {}

    #[Route('', methods: ['GET'], name: 'index_topology')]
    public function index(): JsonResponse
    {
        $topologies = $this->topologyService->findAll();

        $response = array_map(
            fn($topology) => new TopologyListResponse($topology),
            $topologies
        );

        return $this->json($response);
    }

    #[Route('/{id}', methods: ['GET'], name: 'show_topology')]
    public function show(int $id): JsonResponse
    {
        $topology = $this->topologyService->findById($id);

        if (!$topology) {
            return $this->json(['error' => 'Topology not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(new TopologyResponse($topology));
    }

    #[Route('', methods: ['POST'], name: 'create_topology')]
    public function create(#[MapRequestPayload] SaveTopologyRequest $request): JsonResponse
    {
        $topology = $this->topologyService->create($request);

        return $this->json(new TopologyResponse($topology), Response::HTTP_CREATED);
    }

    #[Route('/{id}', methods: ['PUT'], name: 'update_topology')]
    public function update(int $id, #[MapRequestPayload] SaveTopologyRequest $request): JsonResponse
    {
        $topology = $this->topologyService->findById($id);

        if (!$topology) {
            return $this->json(['error' => 'Topology not found'], Response::HTTP_NOT_FOUND);
        }

        $topology = $this->topologyService->update($topology, $request);

        return $this->json(new TopologyResponse($topology));
    }

    #[Route('/{id}', methods: ['DELETE'], name: 'delete_topology')]
    public function delete(int $id): JsonResponse
    {
        $topology = $this->topologyService->findById($id);

        if (!$topology) {
            return $this->json(['error' => 'Topology not found'], Response::HTTP_NOT_FOUND);
        }

        $this->topologyService->delete($topology);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
