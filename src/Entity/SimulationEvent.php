<?php

namespace App\Entity;

use App\Enum\EventStatus;
use App\Repository\SimulationEventRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SimulationEventRepository::class)]
class SimulationEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'events')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Simulation $simulation = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TopologyNode $sourceNode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TopologyNode $targetNode = null;

    #[ORM\Column]
    private ?int $latency = null;

    #[ORM\Column]
    private array $payload = [];

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'string', enumType: EventStatus::class)]
    private EventStatus $status;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSimulation(): ?Simulation
    {
        return $this->simulation;
    }

    public function setSimulation(?Simulation $simulation): static
    {
        $this->simulation = $simulation;

        return $this;
    }

    public function getStatus(): EventStatus
    {
        return $this->status;
    }

    public function setStatus(EventStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSourceNode(): ?TopologyNode
    {
        return $this->sourceNode;
    }

    public function setSourceNode(?TopologyNode $sourceNode): static
    {
        $this->sourceNode = $sourceNode;

        return $this;
    }

    public function getTargetNode(): ?TopologyNode
    {
        return $this->targetNode;
    }

    public function setTargetNode(?TopologyNode $targetNode): static
    {
        $this->targetNode = $targetNode;

        return $this;
    }

    public function getLatency(): ?int
    {
        return $this->latency;
    }

    public function setLatency(int $latency): static
    {
        $this->latency = $latency;

        return $this;
    }

    public function getPayload(): array
    {
        return $this->payload;
    }

    public function setPayload(array $payload): static
    {
        $this->payload = $payload;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
