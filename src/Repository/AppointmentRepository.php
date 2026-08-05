<?php

namespace App\Repository;

use App\Entity\Appointment;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Appointment> */
class AppointmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Appointment::class);
    }

    public function findUpcomingByUser(User $user): array
    {
        return $this->createQueryBuilder('ap')
            ->leftJoin('ap.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->leftJoin('ap.animals', 'ma')
            ->leftJoin('ma.animalShares', 'ms')
            ->where('ap.scheduledAt >= :now')
            ->andWhere('ap.createdBy = :user OR ap.sharedWithProfessional = :user OR ap.client = :user OR a.owner = :user OR s.sharedWithEmail = :email OR ma.owner = :user OR ms.sharedWithEmail = :email')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->groupBy('ap.id')
            ->orderBy('ap.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByPeriodAndUser(\DateTimeImmutable $start, \DateTimeImmutable $end, User $user): array
    {
        return $this->createQueryBuilder('ap')
            ->leftJoin('ap.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->leftJoin('ap.animals', 'ma')
            ->leftJoin('ma.animalShares', 'ms')
            ->where('ap.scheduledAt BETWEEN :start AND :end')
            ->andWhere('ap.createdBy = :user OR ap.sharedWithProfessional = :user OR ap.client = :user OR a.owner = :user OR s.sharedWithEmail = :email OR ma.owner = :user OR ms.sharedWithEmail = :email')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->groupBy('ap.id')
            ->orderBy('ap.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
