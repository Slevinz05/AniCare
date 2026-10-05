<?php

namespace App\Repository;

use App\Entity\Reminder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Reminder> */
class ReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reminder::class);
    }

    public function findActiveByUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.owner = :user OR r.createdBy = :user')
            ->andWhere('r.active = true')
            ->orderBy('r.nextOccurrence', 'ASC')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    public function findByPeriodAndUser(\DateTimeImmutable $start, \DateTimeImmutable $end, User $user): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.owner = :user OR r.createdBy = :user')
            ->andWhere('r.active = true')
            ->andWhere('r.nextOccurrence BETWEEN :start AND :end')
            ->setParameter('user', $user)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->orderBy('r.nextOccurrence', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findTodayByUser(User $user): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return $this->createQueryBuilder('r')
            ->where('r.owner = :user OR r.createdBy = :user')
            ->andWhere('r.active = true')
            ->andWhere('r.nextOccurrence >= :today')
            ->andWhere('r.nextOccurrence < :tomorrow')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->orderBy('r.nextOccurrence', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOverdueByUser(User $user): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('r')
            ->where('r.owner = :user OR r.createdBy = :user')
            ->andWhere('r.active = true')
            ->andWhere('r.nextOccurrence < :today')
            ->setParameter('user', $user)
            ->setParameter('today', $today)
            ->orderBy('r.nextOccurrence', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.owner = :user OR r.createdBy = :user')
            ->andWhere('r.active = true')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
