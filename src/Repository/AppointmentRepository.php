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
            ->join('ap.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('ap.scheduledAt >= :now')
            ->andWhere('a.owner = :user OR s.sharedWithEmail = :email OR ap.createdBy = :user')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('ap.scheduledAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
