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

    public function findAccessibleByUser(User $user, bool $isPro = false, array $filters = []): array
    {
        if ($isPro) {
            $qb = $this->createQueryBuilder('h')
                ->leftJoin('h.animal', 'a')
                ->where('h.veterinarian = :user')
                ->setParameter('user', $user);
        } else {
            $qb = $this->createQueryBuilder('h')
                ->innerJoin('h.animal', 'a')
                ->leftJoin('a.animalShares', 's')
                ->where('(a.owner = :user OR s.sharedWithEmail = :email)')
                ->andWhere('h.status IN (:visibleStatuses) OR h.veterinarian = :user OR h.createdBy = :user')
                ->setParameter('user', $user)
                ->setParameter('email', $user->getEmail())
                ->setParameter('visibleStatuses', ['published', 'shared']);
        }

        if (!empty($filters['animal_id'])) {
            $qb->andWhere('a.id = :animalId')
                ->setParameter('animalId', $filters['animal_id']);
        }

        if (!empty($filters['type'])) {
            $qb->andWhere('h.type = :type')
                ->setParameter('type', $filters['type']);
        }

        if (!empty($filters['date_from'])) {
            $qb->andWhere('h.date >= :dateFrom')
                ->setParameter('dateFrom', new \DateTimeImmutable($filters['date_from']));
        }

        if (!empty($filters['date_to'])) {
            $qb->andWhere('h.date <= :dateTo')
                ->setParameter('dateTo', new \DateTimeImmutable($filters['date_to'] . ' 23:59:59'));
        }

        if (!empty($filters['status'])) {
            $qb->andWhere('h.status = :filterStatus')
                ->setParameter('filterStatus', $filters['status']);
        }

        if (!empty($filters['q'])) {
            $qb->andWhere('LOWER(a.name) LIKE LOWER(:searchQ) OR LOWER(h.title) LIKE LOWER(:searchQ) OR LOWER(h.description) LIKE LOWER(:searchQ)')
                ->setParameter('searchQ', '%' . $filters['q'] . '%');
        }

        return $qb->orderBy('h.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findRecentByVeterinarian(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.veterinarian = :user')
            ->andWhere('h.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'published')
            ->orderBy('h.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findDraftsByVeterinarian(User $user, int $limit = 5): array
    {
        return $this->createQueryBuilder('h')
            ->where('h.veterinarian = :user')
            ->andWhere('h.status = :status')
            ->setParameter('user', $user)
            ->setParameter('status', 'draft')
            ->orderBy('h.date', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findTodaysRemindersByVeterinarian(User $user): array
    {
        $today = new \DateTimeImmutable('today');
        $tomorrow = $today->modify('+1 day');

        return $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('h.nextReminderAt >= :today')
            ->andWhere('h.nextReminderAt < :tomorrow')
            ->andWhere('(a.owner = :user OR s.sharedWithEmail = :email OR h.veterinarian = :user)')
            ->setParameter('today', $today)
            ->setParameter('tomorrow', $tomorrow)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->orderBy('h.nextReminderAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingRemindersByOwner(User $user, int $limit = 3): array
    {
        return $this->createQueryBuilder('h')
            ->innerJoin('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('a.owner = :user OR s.sharedWithEmail = :email')
            ->andWhere('h.date >= :today')
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->setParameter('today', new \DateTimeImmutable('today'))
            ->orderBy('h.date', 'ASC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByMonthAndUser(\DateTimeImmutable $start, \DateTimeImmutable $end, User $user, array $structureIds = []): array
    {
        $qb = $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->leftJoin('a.animalShares', 's')
            ->where('h.date BETWEEN :start AND :end')
            ->andWhere('h.status != :draft')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('user', $user)
            ->setParameter('email', $user->getEmail())
            ->setParameter('draft', 'draft');

        $conditions = 'a.owner = :user OR s.sharedWithEmail = :email';

        if (!empty($structureIds)) {
            $conditions .= ' OR a.structure IN (:structureIds)';
            $qb->setParameter('structureIds', $structureIds);
        }

        return $qb->andWhere($conditions)
            ->orderBy('h.date', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByDate(\DateTimeImmutable $date): array
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.date = :targetDate')
            ->setParameter('targetDate', $date->format('Y-m-d'))
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingReminders(int $daysAhead = 7): array
    {
        $today = new \DateTimeImmutable('today');
        $threshold = $today->modify("+{$daysAhead} days");

        return $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->join('a.owner', 'u')
            ->where('h.nextReminderAt IS NOT NULL')
            ->andWhere('h.nextReminderAt BETWEEN :today AND :threshold')
            ->andWhere('u.acceptsNotifications = true')
            ->setParameter('today', $today)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    public function findOverdueReminders(): array
    {
        $today = new \DateTimeImmutable('today');

        return $this->createQueryBuilder('h')
            ->join('h.animal', 'a')
            ->join('a.owner', 'u')
            ->where('h.nextReminderAt IS NOT NULL')
            ->andWhere('h.nextReminderAt < :today')
            ->andWhere('u.acceptsNotifications = true')
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();
    }
}
