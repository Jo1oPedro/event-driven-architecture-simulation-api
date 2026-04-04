<?php

namespace App\Controller;

use App\DTO\Response\TopologyListResponse;
use App\Service\TopologyService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
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
}
