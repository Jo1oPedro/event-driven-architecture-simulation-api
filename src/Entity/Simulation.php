<?php

namespace App\Entity;

use App\Enum\SimulationStatus;
use App\Repository\SimulationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SimulationRepository::class)]
class Simulation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Topology $topology = null;

    #[ORM\Column(type: 'string', enumType: SimulationStatus::class)]
    private SimulationStatus $status = SimulationStatus::Pending;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $completedAt = null;

    /**
     * @var Collection<int, SimulationEvent>
     */
    #[ORM\OneToMany(targetEntity: SimulationEvent::class, mappedBy: 'simulation', orphanRemoval: true)]
    private Collection $events;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

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

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(?\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getCompletedAt(): ?\DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?\DateTimeImmutable $completedAt): static
    {
        $this->completedAt = $completedAt;

        return $this;
    }

    public function getStatus(): SimulationStatus
    {
        return $this->status;
    }

    public function setStatus(SimulationStatus $status): static
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return Collection<int, SimulationEvent>
     */
    public function getEvents(): Collection
    {
        return $this->events;
    }

    public function addEvent(SimulationEvent $event): static
    {
        if (!$this->events->contains($event)) {
            $this->events->add($event);
            $event->setSimulation($this);
        }

        return $this;
    }

    public function removeEvent(SimulationEvent $event): static
    {
        if ($this->events->removeElement($event)) {
            if ($event->getSimulation() === $this) {
                $event->setSimulation(null);
            }
        }

        return $this;
    }
}
