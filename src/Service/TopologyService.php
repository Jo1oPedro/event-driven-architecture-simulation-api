<?php

namespace App\Service;

use App\DTO\Request\SaveTopologyRequest;
use App\DTO\Request\TopologyEdgeRequest;
use App\DTO\Request\TopologyNodeRequest;
use App\Entity\Topology;
use App\Entity\TopologyEdge;
use App\Entity\TopologyNode;
use App\Repository\TopologyRepository;
use Doctrine\ORM\EntityManagerInterface;

class TopologyService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TopologyRepository     $topologyRepository
    )
    {
    }

    /**
     * @return Topology[]
     */
    public function findAll(): array
    {
        return $this->topologyRepository->findAll();
    }

    public function findById(string $id): ?Topology
    {
        return $this->topologyRepository->findById($id);
    }

    public function create(SaveTopologyRequest $request): Topology
    {
        $topology = new Topology();
        $topology->setName($request->name);
        $topology->setDescription($request->description);
        $topology->setCreatedAt(new \DateTimeImmutable());

        $nodeMap = $this->createNodes($request->nodes);

        foreach ($nodeMap as $node) {
            $topology->addNode($node);
        }

        $edgeMap = $this->createEdges($request->edges, $nodeMap);

        foreach ($edgeMap as $edge) {
            $topology->addEdge($edge);
        }

        $this->entityManager->persist($topology);
        $this->entityManager->flush();

        return $topology;
    }

    /**
     * @param TopologyNodeRequest[] $nodes
     * @return TopologyNode[]
     */
    private function createNodes(array $nodes): array
    {
        $nodeMap = [];
        foreach ($nodes as $nodeRequest) {
            $node = new TopologyNode();
            $node->setType($nodeRequest->type);
            $node->setLabel($nodeRequest->label);
            $node->setPositionX($nodeRequest->positionX);
            $node->setPositionY($nodeRequest->positionY);
            $node->setConfig($nodeRequest->config);

            $nodeMap[$nodeRequest->clientId] = $node;
        }

        return $nodeMap;
    }

    /**
     * @param TopologyEdgeRequest[] $edges
     * @param TopologyNode[] $nodes
     * @return TopologyEdge[]
     */
    public function createEdges(array $edges, array $nodes): array
    {
        $edgeMap = [];
        foreach ($edges as $edgeRequest) {
            $edge = new TopologyEdge();
            $edge->setSourceNode($nodes[$edgeRequest->source]);
            $edge->setTargetNode($nodes[$edgeRequest->target]);
            $edge->setSimulatedLatency($edgeRequest->simulatedLatency);
            $edge->setFailureRate($edgeRequest->failureRate);

            $edgeMap[] = $edge;
        }

        return $edgeMap;
    }

    public function update(Topology $topology, SaveTopologyRequest $request): Topology
    {
        $topology->setName($request->name);
        $topology->setDescription($request->description);
        $topology->setUpdatedAt(new \DateTimeImmutable());

        foreach($topology->getNodes()->toArray() as $node) {
            $topology->removeNode($node);
        }

        foreach($topology->getEdges()->toArray() as $edge) {
            $topology->removeEdge($edge);
        }

        $nodeMap = $this->createNodes($request->nodes);

        foreach ($nodeMap as $node) {
            $topology->addNode($node);
        }

        $edgeMap = $this->createEdges($request->edges, $nodeMap);

        foreach ($edgeMap as $edge) {
            $topology->addEdge($edge);
        }

        $this->entityManager->flush();
        return $topology;
    }

    public function delete(Topology $topology): void
    {
        $this->entityManager->remove($topology);
        $this->entityManager->flush();
    }
}
