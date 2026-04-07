<?php

namespace App\Repository;

use App\Entity\SimulationEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SimulationEvent>
 */
class SimulationEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SimulationEvent::class);
    }

    public function countBySimulation(int $simulationId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.simulation = :simulationId')
            ->setParameter('simulationId', $simulationId)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
