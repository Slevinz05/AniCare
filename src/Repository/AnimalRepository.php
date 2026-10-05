<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\Structure;
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
            ->leftJoin('a.structure', 'st')
            ->leftJoin('st.memberships', 'm')
            ->where('a.owner = :user')
            ->orWhere('s.sharedWithEmail = :email')
            ->orWhere('m.user = :user')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->groupBy('a.id')
            ->getQuery()
            ->getResult();
    }

    public function findByStructureAndPro(Structure $structure, User $pro): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DISTINCT a.id FROM animal a
            WHERE a.structure_id = :structureId
            AND (
                a.id IN (SELECT hbe.animal_id FROM health_book_entry hbe WHERE hbe.veterinarian_id = :proId)
                OR a.id IN (SELECT aa.animal_id FROM appointment_animal aa JOIN appointment ap ON ap.id = aa.appointment_id WHERE ap.created_by_id = :proId)
                OR a.id IN (SELECT ap2.animal_id FROM appointment ap2 WHERE ap2.animal_id IS NOT NULL AND ap2.created_by_id = :proId)
            )
        ';

        $ids = $conn->executeQuery($sql, [
            'structureId' => $structure->getId(),
            'proId' => $pro->getId(),
        ])->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        return $this->createQueryBuilder('a')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?Animal
    {
        return $this->findOneBy(['slug' => $slug]);
    }

    public function searchByQuery(string $query, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.owner', 'o')
            ->where('LOWER(a.name) LIKE LOWER(:q)')
            ->orWhere('a.identificationNumber LIKE :q')
            ->orWhere('a.microchipNumber LIKE :q')
            ->orWhere("CONCAT(LOWER(o.firstName), ' ', LOWER(o.lastName)) LIKE LOWER(:q)")
            ->setParameter('q', '%' . $query . '%')
            ->orderBy('a.name', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findDuplicates(string $name, ?string $identificationNumber, ?string $microchipNumber): array
    {
        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.owner', 'o');

        $conditions = [];

        if ($identificationNumber) {
            $conditions[] = 'a.identificationNumber = :idNum';
            $qb->setParameter('idNum', $identificationNumber);
        }

        if ($microchipNumber) {
            $conditions[] = 'a.microchipNumber = :chip';
            $qb->setParameter('chip', $microchipNumber);
        }

        $conditions[] = 'LOWER(a.name) = LOWER(:name)';
        $qb->setParameter('name', $name);

        $qb->where(implode(' OR ', $conditions))
            ->orderBy('a.name', 'ASC');

        return $qb->getQuery()->getResult();
    }
}
