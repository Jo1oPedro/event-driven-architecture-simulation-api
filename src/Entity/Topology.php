<?php

namespace App\Entity;

use App\Repository\TopologyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TopologyRepository::class)]
class Topology
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    /**
     * @var Collection<int, TopologyNode>
     */
    #[ORM\OneToMany(targetEntity: TopologyNode::class, mappedBy: 'topology', orphanRemoval: true)]
    private Collection $nodes;

    /**
     * @var Collection<int, TopologyEdge>
     */
    #[ORM\OneToMany(targetEntity: TopologyEdge::class, mappedBy: 'topology', orphanRemoval: true)]
    private Collection $edges;

    public function __construct()
    {
        $this->nodes = new ArrayCollection();
        $this->edges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, TopologyNode>
     */
    public function getNodes(): Collection
    {
        return $this->nodes;
    }

    public function addNode(TopologyNode $node): static
    {
        if (!$this->nodes->contains($node)) {
            $this->nodes->add($node);
            $node->setTopology($this);
        }

        return $this;
    }

    public function removeNode(TopologyNode $node): static
    {
        if ($this->nodes->removeElement($node)) {
            // set the owning side to null (unless already changed)
            if ($node->getTopology() === $this) {
                $node->setTopology(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, TopologyEdge>
     */
    public function getEdges(): Collection
    {
        return $this->edges;
    }

    public function addEdge(TopologyEdge $edge): static
    {
        if (!$this->edges->contains($edge)) {
            $this->edges->add($edge);
            $edge->setTopology($this);
        }

        return $this;
    }

    public function removeEdge(TopologyEdge $edge): static
    {
        if ($this->edges->removeElement($edge)) {
            // set the owning side to null (unless already changed)
            if ($edge->getTopology() === $this) {
                $edge->setTopology(null);
            }
        }

        return $this;
    }

}
