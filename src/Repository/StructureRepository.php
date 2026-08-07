<?php

namespace App\Repository;

use App\Entity\Structure;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Structure>
 */
class StructureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Structure::class);
    }

    public function findByUser(User $user): array
    {
        return $this->createQueryBuilder('s')
            ->join('s.memberships', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllOrderedByName(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function search(string $query): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.name LIKE :q')
            ->orWhere('s.city LIKE :q')
            ->orWhere('s.postalCode LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByClaimCode(string $code): ?Structure
    {
        return $this->findOneBy(['claimCode' => strtoupper($code)]);
    }

    public function findUnclaimedStructures(): array
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.memberships', 'm', 'WITH', 'm.role = :manager')
            ->groupBy('s.id')
            ->having('COUNT(m.id) = 0')
            ->setParameter('manager', 'MANAGER')
            ->orderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
