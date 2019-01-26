<?php

namespace App\Repository;

use App\Entity\ADS;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method ADS|null find($id, $lockMode = null, $lockVersion = null)
 * @method ADS|null findOneBy(array $criteria, array $orderBy = null)
 * @method ADS[]    findAll()
 * @method ADS[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ADSRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, ADS::class);
    }

    // /**
    //  * @return ADS[] Returns an array of ADS objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ADS
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
