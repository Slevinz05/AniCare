<?php

namespace App\Repository;

use App\Entity\HealthBookEntry;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HealthBookEntry>
 */
class HealthBookEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HealthBookEntry::class);
    }

    public function findAccessibleByUser(User $user): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('a.owner = :user')
            ->orWhere('s.sharedWithEmail = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('h.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingRemindersByOwner(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('a.owner = :user OR s.sharedWithEmail = :email')
            ->andWhere('h.date >= :today')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('h.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByMonthAndUser(\DateTimeImmutable $start, \DateTimeImmutable $end, User $user): array
    {
        return $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('h.date BETWEEN :start AND :end')
            ->andWhere('a.owner = :user OR s.sharedWithEmail = :email')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('h.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.date = :targetDate')
            ->setParameter('targetDate', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }
}
