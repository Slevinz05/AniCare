<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Animal>
 */
class AnimalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Animal::class);
    }

    public function findAccessibleAnimals(User $user): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.animalShares', 's')
            ->where('a.owner = :user')
            ->orWhere('s.sharedWithEmail = :email')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?Animal
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
