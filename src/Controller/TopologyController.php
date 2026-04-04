<?php

namespace App\Controller;

use App\DTO\Request\SaveTopologyRequest;
use App\DTO\Response\TopologyListResponse;
use App\DTO\Response\TopologyResponse;
use App\Service\TopologyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class TopologyController extends AbstractController
{
    public function __construct(
        private readonly TopologyService $topologyService
    ) {}

    #[Route('/', methods: ['GET'], name: 'index_topology')]
    public function index(): JsonResponse
    {
        $topologies = $this->topologyService->findAll();

        $response = array_map(
            function($topology) {
                new TopologyListResponse($topology);
            },
            $topologies
        );

        return new JsonResponse($response);
    }

    #[Route('/{id}', methods: ['GET'], name: 'show_topology')]
    public function show(int $id): JsonResponse
    {
        $topology = $this->topologyService->findById($id);

        if(!$topology) {
            return $this->json(['error' => 'Typology not found'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(new TopologyResponse($topology));
    }

    #[Route('', methods: ['POST'], name: 'create_topology')]
    public function create(Request $request): JsonResponse
    {
        $data = json_encode($request->getContent(), true);

        $topologyRequest = new SaveTopologyRequest(
            name: $data['name'],
            description: $data['description'] ?? null,
            nodes: $data['nodes'] ?? [],
            edges: $data['edges'] ?? [],
        );

        $topology = $this->topologyService->create($topologyRequest);

        return $this->json(
            new TopologyResponse($topology),
            Response::HTTP_CREATED
        );
    }

    #[Route('/{id}', methods: ['PUT'], name: 'update_topology')]
    public function update(int $id, Request $request): JsonResponse
    {
        $topology = $this->topologyService->findById($id);

        if(!$topology) {
            return $this->json(['error' => 'Topology not found'], Response::HTTP_NOT_FOUND);
        }

        $topologyRequest = new SaveTopologyRequest(
            name: $topology['name'],
            description: $topology['description'] ?? null,
            nodes: $topology['nodes'] ?? [],
            edges: $topology['edges'] ?? [],
        );

        $topology = $this->topologyService->update($topology, $topologyRequest);

        return $this->json(new TopologyResponse($topology));
    }
}
