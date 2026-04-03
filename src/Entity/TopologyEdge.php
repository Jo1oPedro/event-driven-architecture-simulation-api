<?php

namespace App\Entity;

use App\Repository\TopologyEdgeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopologyEdgeRepository::class)]
class TopologyEdge
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'edges')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Topology $topology = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TopologyNode $sourceNode = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?TopologyNode $targetNode = null;

    #[ORM\Column]
    private ?int $simulatedLatency = null;

    #[ORM\Column]
    private ?float $failureRate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTopology(): ?Topology
    {
        return $this->topology;
    }

    public function setTopology(?Topology $topology): static
    {
        $this->topology = $topology;

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

    public function getSimulatedLatency(): ?int
    {
        return $this->simulatedLatency;
    }

    public function setSimulatedLatency(int $simulatedLatency): static
    {
        $this->simulatedLatency = $simulatedLatency;

        return $this;
    }

    public function getFailureRate(): ?float
    {
        return $this->failureRate;
    }

    public function setFailureRate(float $failureRate): static
    {
        $this->failureRate = $failureRate;

        return $this;
    }
}
