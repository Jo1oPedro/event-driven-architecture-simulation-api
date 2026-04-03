<?php

namespace App\Message;

final class SimulationMessage
{
    public function __construct(
        private readonly int $simulationId,
        private readonly int $sourceNodeId,
        private readonly int $targetNodeId,
        private readonly int $edgeId,
        private readonly array $payload = [],
    ) {
    }

    public function getSimulationId(): int
    {
        return $this->simulationId;
    }

    public function getSourceNodeId(): int
    {
        return $this->sourceNodeId;
    }

    public function getTargetNodeId(): int
    {
        return $this->targetNodeId;
    }

    public function getEdgeId(): int
    {
        return $this->edgeId;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }
}
