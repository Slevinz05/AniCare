<?php

namespace App\Repository;

use App\Entity\AnimalShare;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnimalShare>
 */
class AnimalShareRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnimalShare::class);
    }

    public function findByOwner(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a')
            ->where('a.owner = :user')
            ->setParameter('user', $user)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
