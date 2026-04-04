<?php

namespace App\DTO\Response;

use App\Entity\TopologyEdge;

class TopologyEdgeResponse
{
    public readonly int $id;
    public readonly int $sourceNodeId;
    public readonly int $targetNodeId;
    public readonly int $simulatedLatency;
    public readonly float $failureRate;

    public function __construct(TopologyEdge $edge)
    {
        $this->id = $edge->getId();
        $this->sourceNodeId = $edge->getSourceNode()->getId();
        $this->targetNodeId = $edge->getTargetNode()->getId();
        $this->simulatedLatency = $edge->getSimulatedLatency();
        $this->failureRate = $edge->getFailureRate();
    }
}
