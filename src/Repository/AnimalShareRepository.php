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

    public function findSharedWithEmail(string $email): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a')
            ->addSelect('a')
            ->where('s.sharedWithEmail = :email')
            ->setParameter('email', $email)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function searchSharedWithEmail(string $email, ?string $query = null): array
    {
        $qb = $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a')
            ->innerJoin('a.owner', 'o')
            ->addSelect('a', 'o')
            ->where('s.sharedWithEmail = :email')
            ->setParameter('email', $email);

        if ($query) {
            $qb->andWhere(
                'LOWER(o.firstName) LIKE :q OR LOWER(o.lastName) LIKE :q '
                . 'OR LOWER(a.name) LIKE :q '
                . 'OR LOWER(a.livingPlaceName) LIKE :q '
                . 'OR LOWER(a.livingPlaceCity) LIKE :q'
            )
            ->setParameter('q', '%' . mb_strtolower($query) . '%');
        }

        return $qb
            ->orderBy('o.lastName', 'ASC')
            ->addOrderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findClientsByProfessionalEmail(string $email): array
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.animal', 'a')
            ->innerJoin('a.owner', 'o')
            ->addSelect('o')
            ->where('s.sharedWithEmail = :email')
            ->setParameter('email', $email)
            ->groupBy('o.id')
            ->orderBy('o.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
