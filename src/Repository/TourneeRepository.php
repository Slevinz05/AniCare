<?php

namespace App\Repository;

use App\Entity\Tournee;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Tournee> */
class TourneeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tournee::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.createdBy = :user')
            ->orderBy('t.date', 'DESC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingByUser(User $user, int $limit = 5): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('t')
            ->where('t.createdBy = :user')
            ->andWhere('t.date >= :today')
            ->orderBy('t.date', 'ASC')
            ->setMaxResults($limit)
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }

    public function findTodayByUser(User $user): ?Tournee
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('t')
            ->where('t.createdBy = :user')
            ->andWhere('t.date = :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
