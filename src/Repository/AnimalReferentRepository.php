<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\AnimalReferent;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnimalReferent>
 */
class AnimalReferentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnimalReferent::class);
    }

    public function findPrincipal(Animal $animal): ?AnimalReferent
    {
        return $this->findOneBy([
            'animal' => $animal,
            'type' => AnimalReferent::TYPE_PRINCIPAL,
            'status' => AnimalReferent::STATUS_ACTIVE,
        ]);
    }

    public function findActiveReferents(Animal $animal): array
    {
        return $this->findBy([
            'animal' => $animal,
            'status' => AnimalReferent::STATUS_ACTIVE,
        ], ['type' => 'ASC', 'designatedAt' => 'ASC']);
    }

    public function findSecondaires(Animal $animal): array
    {
        return $this->findBy([
            'animal' => $animal,
            'type' => AnimalReferent::TYPE_SECONDAIRE,
            'status' => AnimalReferent::STATUS_ACTIVE,
        ]);
    }

    public function findAnimalsForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->select('IDENTITY(r.animal)')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', AnimalReferent::STATUS_ACTIVE)
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function isReferent(Animal $animal, User $user): bool
    {
        return (bool) $this->findOneBy([
            'animal' => $animal,
            'user' => $user,
            'status' => AnimalReferent::STATUS_ACTIVE,
        ]);
    }

    public function isPrincipal(Animal $animal, User $user): bool
    {
        return (bool) $this->findOneBy([
            'animal' => $animal,
            'user' => $user,
            'type' => AnimalReferent::TYPE_PRINCIPAL,
            'status' => AnimalReferent::STATUS_ACTIVE,
        ]);
    }

    public function findPendingForUser(User $user): array
    {
        return $this->createQueryBuilder('r')
            ->innerJoin('r.animal', 'a')
            ->innerJoin('r.designatedBy', 'd')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', AnimalReferent::STATUS_PENDING)
            ->orderBy('r.designatedAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countPendingForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', AnimalReferent::STATUS_PENDING)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findAllForAnimal(Animal $animal): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.animal = :animal')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('animal', $animal)
            ->setParameter('statuses', [AnimalReferent::STATUS_ACTIVE, AnimalReferent::STATUS_PENDING])
            ->orderBy('r.type', 'ASC')
            ->addOrderBy('r.designatedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingTransfer(Animal $animal): ?AnimalReferent
    {
        return $this->findOneBy([
            'animal' => $animal,
            'type' => AnimalReferent::TYPE_PRINCIPAL,
            'status' => AnimalReferent::STATUS_PENDING,
        ]);
    }

    public function hasExistingRelation(Animal $animal, User $user): bool
    {
        return (bool) $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.animal = :animal')
            ->andWhere('r.user = :user')
            ->andWhere('r.status IN (:statuses)')
            ->setParameter('animal', $animal)
            ->setParameter('user', $user)
            ->setParameter('statuses', [AnimalReferent::STATUS_ACTIVE, AnimalReferent::STATUS_PENDING])
            ->getQuery()
            ->getSingleScalarResult();
    }
}
