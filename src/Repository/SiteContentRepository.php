<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SiteContent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SiteContent>
 */
final class SiteContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SiteContent::class);
    }

    /**
     * The table holds a single row, seeded by migration and protected from creation and deletion
     * in the back-office. Null means the row is missing, which the templates must survive.
     */
    public function findCurrent(): ?SiteContent
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
