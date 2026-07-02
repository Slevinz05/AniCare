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

    /**
     * Récupère les prochains rappels ou soins médicaux pour les animaux d'un propriétaire
     */
    public function findUpcomingRemindersByOwner(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.animal', 'a')
            ->andWhere('a.owner = :user')
            // Logique : Échéances à partir d'aujourd'hui
            ->andWhere('h.date >= :today')
            ->setParameter('user', $user)
            ->setParameter('today', new \DateTimeImmutable('today')) // Utilisation d'un DateTimeImmutable calé à 00:00:00
            // Tri chronologique : le plus proche dans le temps en premier
            ->orderBy('h.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les entrées médicales pour une date donnée (sans prendre en compte les heures)
     */
    public function findByDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.date = :targetDate')
            ->setParameter('targetDate', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }
}