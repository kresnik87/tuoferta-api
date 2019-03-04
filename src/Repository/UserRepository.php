<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOneByTelephone($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.telephone = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    
    public function findOneByEmail($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.email = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    
    public function simpleFind($value){
        return $this->createQueryBuilder('u')
                ->select('u.name, u.address, u.email, u.telephone, u.id')
                ->where('u.id = :val')
                ->setParameter('val', $value)
                ->getQuery()
                ->getResult();
    }

    public function checkEmail($value)
    {
        return count(
                $this->createQueryBuilder('u')
                        ->andWhere('u.email = :val')
                        ->setParameter('val', $value)
                        ->getQuery()
                        ->getResult()
                )
        ;
    }
}
