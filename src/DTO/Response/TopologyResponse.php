<?php

namespace App\DTO\Response;

use App\Entity\Topology;
use App\Entity\TopologyEdge;
use App\Entity\TopologyNode;

class TopologyResponse
{
    public readonly int $id;
    public readonly string $name;
    public readonly ?string $description;
    public readonly string $createdAt;
    public readonly ?string $updatedAt;

    /** @var TopologyNodeResponse[] */
    public readonly array $nodes;

    /** @var TopologyEdgeResponse[] */
    public readonly array $edges;
    public function __construct(Topology $topology)
    {
        $this->id = $topology->getId();
        $this->name = $topology->getName();
        $this->description = $topology->getDescription();
        $this->createdAt = $topology->getCreatedAt()->format('c');  // ISO 8601
        $this->updatedAt = $topology->getUpdatedAt()?->format('c');

        $this->nodes = $topology->getNodes()->map(
            function (TopologyNode $node) {
                return new TopologyNodeResponse($node);
            }
        )->toArray();

        $this->edges = $topology->getEdges()->map(
            function (TopologyEdge $edge) {
                return new TopologyEdgeResponse($edge);
            }
        )->toArray();
    }
}
