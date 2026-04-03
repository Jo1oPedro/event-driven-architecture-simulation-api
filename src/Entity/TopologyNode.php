<?php

namespace App\Entity;

use App\Enum\NodeType;
use App\Repository\TopologyNodeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopologyNodeRepository::class)]
class TopologyNode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'nodes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Topology $topology = null;

    #[ORM\Column(length: 255)]
    private ?string $label = null;

    #[ORM\Column]
    private ?float $positionX = null;

    #[ORM\Column]
    private ?float $positionY = null;

    #[ORM\Column]
    private array $config = [];

    #[ORM\Column(type: 'string', enumType: NodeType::class)]
    private NodeType $type;

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

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    public function getPositionX(): ?float
    {
        return $this->positionX;
    }

    public function setPositionX(float $positionX): static
    {
        $this->positionX = $positionX;

        return $this;
    }

    public function getPositionY(): ?float
    {
        return $this->positionY;
    }

    public function setPositionY(float $positionY): static
    {
        $this->positionY = $positionY;

        return $this;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function getType(): NodeType
    {
        return $this->type;
    }

    public function setType(NodeType $type): static
    {
        $this->type = $type;
        return $this;
    }
}
