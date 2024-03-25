<?php

namespace App\Repository;

use App\Entity\PersonalProject;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PersonalProject>
 *
 * @method PersonalProject|null find($id, $lockMode = null, $lockVersion = null)
 * @method PersonalProject|null findOneBy(array $criteria, array $orderBy = null)
 * @method PersonalProject[]    findAll()
 * @method PersonalProject[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PersonalProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PersonalProject::class);
    }

//    /**
//     * @return PersonalProject[] Returns an array of PersonalProject objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PersonalProject
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
