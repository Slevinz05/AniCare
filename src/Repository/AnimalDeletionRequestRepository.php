<?php

namespace App\Repository;

use App\Entity\AnimalDeletionRequest;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnimalDeletionRequest>
 */
class AnimalDeletionRequestRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnimalDeletionRequest::class);
    }

    public function findPendingForOwner(User $owner): array
    {
        return $this->createQueryBuilder('r')
            ->join('r.animal', 'a')
            ->where('a.owner = :owner')
            ->andWhere('r.status = :status')
            ->setParameter('owner', $owner)
            ->setParameter('status', AnimalDeletionRequest::STATUS_PENDING)
            ->orderBy('r.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingForAnimal(int $animalId): ?AnimalDeletionRequest
    {
        return $this->createQueryBuilder('r')
            ->where('r.animal = :animalId')
            ->andWhere('r.status = :status')
            ->setParameter('animalId', $animalId)
            ->setParameter('status', AnimalDeletionRequest::STATUS_PENDING)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
