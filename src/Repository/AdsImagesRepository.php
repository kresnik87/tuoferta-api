<?php

namespace App\Repository;

use App\Entity\VehicleImages;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method VehicleImages|null find($id, $lockMode = null, $lockVersion = null)
 * @method VehicleImages|null findOneBy(array $criteria, array $orderBy = null)
 * @method VehicleImages[]    findAll()
 * @method VehicleImages[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AdsImagesRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, VehicleImages::class);
    }

    // /**
    //  * @return VehicleImages[] Returns an array of VehicleImages objects
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
    public function findOneBySomeField($value): ?VehicleImages
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
