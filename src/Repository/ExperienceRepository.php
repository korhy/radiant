<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Experience;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Experience>
 */
final class ExperienceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Experience::class);
    }

    /**
     * @return Experience[]
     */
    public function findAllOrderedByStartDate(): array
    {
        return $this->createQueryBuilder('e')
            ->orderBy('e.startDate', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
