<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    /**
     * @return User[]
     */
    public function findProfessionals(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.accountType = :type')
            ->setParameter('type', 'PRO')
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return User[]
     */
    public function findProfessionalsFiltered(?string $specialty, ?string $department, ?string $query): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.accountType = :type')
            ->setParameter('type', 'PRO');

        if ($specialty) {
            $qb->andWhere('u.specialty = :specialty')
               ->setParameter('specialty', $specialty);
        }

        if ($department) {
            $qb->andWhere('u.postalCode LIKE :dept OR u.interventionDepartments LIKE :deptJson')
               ->setParameter('dept', $department . '%')
               ->setParameter('deptJson', '%"' . $department . '"%');
        }

        if ($query) {
            $qb->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.city LIKE :q')
               ->setParameter('q', '%' . $query . '%');
        }

        return $qb->orderBy('u.lastName', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /** @return User[] */
    public function searchProfessionals(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.accountType = :type')
            ->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')
            ->setParameter('type', 'PRO')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function searchOwners(string $query, int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.accountType != :proType')
            ->andWhere('u.firstName LIKE :q OR u.lastName LIKE :q OR u.email LIKE :q')
            ->setParameter('proType', 'PRO')
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('u.lastName', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
