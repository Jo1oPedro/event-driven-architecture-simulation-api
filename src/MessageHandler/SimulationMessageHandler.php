<?php

namespace App\MessageHandler;

use App\Entity\Simulation;
use App\Entity\SimulationEvent;
use App\Enum\EventStatus;
use App\Message\SimulationMessage;
use App\Repository\SimulationEventRepository;
use App\Repository\SimulationRepository;
use App\Repository\TopologyEdgeRepository;
use App\Service\SimulationPublisher;
use App\Service\SimulationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\MessageBusInterface;

#[AsMessageHandler]
class SimulationMessageHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SimulationRepository $simulationRepository,
        private readonly SimulationEventRepository $simulationEventRepository,
        private readonly TopologyEdgeRepository $edgeRepository,
        private readonly MessageBusInterface $messageBus,
        private readonly SimulationPublisher $simulationPublisher,
        private readonly SimulationService $simulationService,
    ) {}

    public function __invoke(SimulationMessage $message): void
    {
        $simulation = $this->simulationRepository->find($message->getSimulationId());
        if (!$simulation) {
            return;
        }

        $edge = $this->edgeRepository->find($message->getEdgeId());
        if (!$edge) {
            return;
        }

        $latencyMs = $edge->getSimulatedLatency();

        // 1. Publicar evento "sent" via Mercure ANTES da latência
        //    Assim o frontend anima a edge imediatamente
        try {
            $this->simulationPublisher->publishEdgeStatus(
                $simulation->getId(),
                $edge->getSourceNode()->getId(),
                $edge->getTargetNode()->getId(),
                EventStatus::Sent,
                $latencyMs,
            );
        } catch (\Throwable) {
        }

        // 2. Simular latência
        if ($latencyMs > 0) {
            usleep($latencyMs * 1000);
        }

        // 3. Decidir falha
        $failureRate = $edge->getFailureRate();
        $failed = (mt_rand(1, 10000) / 10000) <= $failureRate;

        // 4. Registrar SimulationEvent no banco
        $event = new SimulationEvent();
        $event->setSimulation($simulation);
        $event->setSourceNode($edge->getSourceNode());
        $event->setTargetNode($edge->getTargetNode());
        $event->setLatency($latencyMs);
        $event->setStatus($failed ? EventStatus::Failed : EventStatus::Delivered);
        $event->setPayload($message->getPayload());
        $event->setCreatedAt(new \DateTimeImmutable());

        $this->entityManager->persist($event);
        $this->entityManager->flush();

        // 5. Publicar resultado final via Mercure (delivered ou failed)
        try {
            $this->simulationPublisher->publishEvent($event);
        } catch (\Throwable) {
        }

        // 5. Se não falhou, propagar pros próximos nós
        if (!$failed) {
            $targetNode = $edge->getTargetNode();
            $topology = $simulation->getTopology();

            foreach ($topology->getEdges() as $nextEdge) {
                if ($nextEdge->getSourceNode()->getId() === $targetNode->getId()) {
                    $this->messageBus->dispatch(new SimulationMessage(
                        simulationId: $simulation->getId(),
                        sourceNodeId: $targetNode->getId(),
                        targetNodeId: $nextEdge->getTargetNode()->getId(),
                        edgeId: $nextEdge->getId(),
                    ));
                }
            }
        }

        // 6. Verificar se a simulação terminou
        // (não há mais mensagens pendentes no bus — simplificação)
        $this->checkCompletion($simulation);
    }

    private function checkCompletion(Simulation $simulation): void
    {
        $totalEdges = $simulation->getTopology()->getEdges()->count();
        $processedEvents = $this->simulationEventRepository->countBySimulation($simulation->getId());

        if ($processedEvents >= $totalEdges) {
            $this->simulationService->complete($simulation);
        }
    }
}
