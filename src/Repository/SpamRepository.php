<?php

namespace App\Repository;

use App\Entity\Spam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Spam|null find($id, $lockMode = null, $lockVersion = null)
 * @method Spam|null findOneBy(array $criteria, array $orderBy = null)
 * @method Spam[]    findAll()
 * @method Spam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SpamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Spam::class);
    }

    // /**
    //  * @return Spam[] Returns an array of Spam objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Spam
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
