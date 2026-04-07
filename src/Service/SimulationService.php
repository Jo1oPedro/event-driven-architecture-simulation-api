<?php

namespace App\Service;

use App\Entity\Simulation;
use App\Enum\SimulationStatus;
use App\Message\SimulationMessage;
use App\Repository\SimulationRepository;
use App\Repository\TopologyRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class SimulationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SimulationRepository $simulationRepository,
        private readonly TopologyRepository $topologyRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly SimulationPublisher $simulationPublisher,
    ) {}

    public function findById(int $id): ?Simulation
    {
        return $this->simulationRepository->find($id);
    }

    public function start(int $topologyId): ?Simulation
    {
        $topology = $this->topologyRepository->find($topologyId);
        if(!$topology) {
            return null;
        }

        $simulation = new Simulation();
        $simulation->setTopology($topology);
        $simulation->setStatus(SimulationStatus::Running);
        $simulation->setStartedAt(new \DateTimeImmutable());

        $this->entityManager->persist($simulation);
        $this->entityManager->flush();

        if($topology->getEdges()->isEmpty()) {
            $this->complete($simulation);
            return $simulation;
        }

        $allTargetNodeIds = [];
        foreach($topology->getEdges() as $edge) {
            $allTargetNodeIds[] = $edge->getTargetNode()->getId();
        }

        // Para cada nó que não é target de nenhuma edge, é um produtor
        foreach($topology->getNodes() as $node) {
            if(!in_array($node->getId(), $allTargetNodeIds)) {
                foreach($topology->getEdges() as $edge) {
                    if($edge->getSourceNode()->getId() === $node->getId()) {
                        $this->messageBus->dispatch(
                            new SimulationMessage(
                                simulationId: $simulation->getId(),
                                sourceNodeId: $node->getId(),
                                targetNodeId: $edge->getTargetNode()->getId(),
                                edgeId: $edge->getId(),
                            )
                        );
                    }
                }
            }
        }

        try {
            $this->simulationPublisher->publishStatus($simulation);
        } catch (\Throwable) {
            // Mercure indisponível — não bloqueia a simulação
        }

        return $simulation;
    }

    public function complete(Simulation $simulation): void
    {
        $simulation->setStatus(SimulationStatus::Completed);
        $simulation->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        try {
            $this->simulationPublisher->publishStatus($simulation);
        } catch (\Throwable) {
        }
    }

    public function fail(Simulation $simulation): void
    {
        $simulation->setStatus(SimulationStatus::Failed);
        $simulation->setCompletedAt(new \DateTimeImmutable());
        $this->entityManager->flush();

        try {
            $this->simulationPublisher->publishStatus($simulation);
        } catch (\Throwable) {
        }
    }
}
