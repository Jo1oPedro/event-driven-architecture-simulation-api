<?php

namespace App\Service;

use App\Entity\Simulation;
use App\Entity\SimulationEvent;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class SimulationPublisher
{
    public function __construct(
        private readonly HubInterface $hub,
    ) {}

    public function publishEvent(SimulationEvent $event): void
    {
        $simulationId = $event->getSimulation()->getId();

        $update = new Update(
            "simulation/{$simulationId}/events",
            json_encode([
                'id' => $event->getId(),
                'sourceNodeId' => $event->getSourceNode()->getId(),
                'targetNodeId' => $event->getTargetNode()->getId(),
                'status' => $event->getStatus()->value,
                'latency' => $event->getLatency(),
                'createdAt' => $event->getCreatedAt()->format('c')
            ])
        );

        $this->hub->publish($update);
    }

    public function publishStatus(Simulation $simulation): void
    {
        $update = new Update(
            "simulation/{$simulation->getId()}/status",
            json_encode([
                'id' => $simulation->getId(),
                'status' => $simulation->getStatus()->value,
                'completedAt' => $simulation->getCompletedAt()?->format('c')
            ])
        );

        $this->hub->publish($update);
    }
}
