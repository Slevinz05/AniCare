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

    public function findByOwner(User $owner): array
    {
        return $this->createQueryBuilder('a')
            ->leftJoin('a.referents', 'r')
            ->where('a.owner = :owner')
            ->orWhere('r.user = :owner AND r.status = :active')
            ->setParameter('owner', $owner)
            ->setParameter('active', 'active')
            ->groupBy('a.id')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByProHistory(User $pro, ?string $query = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT DISTINCT a.id FROM animal a
            WHERE a.id IN (
                SELECT hbe.animal_id FROM health_book_entry hbe
                WHERE hbe.veterinarian_id = :proId AND hbe.animal_id IS NOT NULL
            )
            OR a.created_by_pro_id = :proId
        ';

        $ids = $conn->executeQuery($sql, ['proId' => $pro->getId()])->fetchFirstColumn();

        if (empty($ids)) {
            return [];
        }

        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.owner', 'o')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('a.name', 'ASC');

        if ($query) {
            $qb->andWhere('LOWER(a.name) LIKE LOWER(:q) OR LOWER(CONCAT(o.firstName, \' \', o.lastName)) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $query . '%');
        }

        return $qb->getQuery()->getResult();
    }

    public function findByProHistoryWithStats(User $pro, ?string $query = null): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT a.id, COUNT(hbe.id) as consultation_count, MAX(hbe.date) as last_consultation_date
            FROM animal a
            LEFT JOIN health_book_entry hbe ON hbe.animal_id = a.id AND hbe.veterinarian_id = :proId
            WHERE a.id IN (
                SELECT hbe2.animal_id FROM health_book_entry hbe2
                WHERE hbe2.veterinarian_id = :proId AND hbe2.animal_id IS NOT NULL
            )
            OR a.created_by_pro_id = :proId
            GROUP BY a.id
        ';

        $rows = $conn->executeQuery($sql, ['proId' => $pro->getId()])->fetchAllAssociative();

        if (empty($rows)) {
            return [];
        }

        $statsMap = [];
        $ids = [];
        foreach ($rows as $row) {
            $ids[] = $row['id'];
            $statsMap[$row['id']] = [
                'consultation_count' => (int) $row['consultation_count'],
                'last_consultation_date' => $row['last_consultation_date'],
            ];
        }

        $qb = $this->createQueryBuilder('a')
            ->leftJoin('a.owner', 'o')
            ->where('a.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->orderBy('a.name', 'ASC');

        if ($query) {
            $qb->andWhere('LOWER(a.name) LIKE LOWER(:q) OR LOWER(CONCAT(o.firstName, \' \', o.lastName)) LIKE LOWER(:q)')
                ->setParameter('q', '%' . $query . '%');
        }

        $animals = $qb->getQuery()->getResult();

        $result = [];
        foreach ($animals as $animal) {
            $stats = $statsMap[$animal->getId()] ?? ['consultation_count' => 0, 'last_consultation_date' => null];
            $result[] = [
                'animal' => $animal,
                'consultation_count' => $stats['consultation_count'],
                'last_consultation_date' => $stats['last_consultation_date'] ? new \DateTimeImmutable($stats['last_consultation_date']) : null,
            ];
        }

        usort($result, function ($a, $b) {
            $dateA = $a['last_consultation_date'];
            $dateB = $b['last_consultation_date'];
            if ($dateA && $dateB) return $dateB <=> $dateA;
            if ($dateA) return -1;
            if ($dateB) return 1;
            return strcmp($a['animal']->getName(), $b['animal']->getName());
        });

        return $result;
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
