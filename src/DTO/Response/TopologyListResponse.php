<?php

namespace App\DTO\Response;

use App\Entity\Topology;

class TopologyListResponse
{
    public readonly int $id;
    public readonly string $name;
    public readonly ?string $description;
    public readonly int $nodeCount;
    public readonly int $edgeCount;
    public readonly string $createdAt;

    public function __construct(Topology $topology)
    {
        $this->id = $topology->getId();
        $this->name = $topology->getName();
        $this->description = $topology->getDescription();
        $this->nodeCount = $topology->getNodes()->count();
        $this->edgeCount = $topology->getEdges()->count();
        $this->createdAt = $topology->getCreatedAt()->format('c');
    }
}
