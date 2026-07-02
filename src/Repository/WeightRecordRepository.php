<?php

namespace App\Repository;

use App\Entity\Animal;
use App\Entity\WeightRecord;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<WeightRecord> */
class WeightRecordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WeightRecord::class);
    }

    public function findByAnimalOrdered(Animal $animal): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.animal = :animal')
            ->setParameter('animal', $animal)
            ->orderBy('w.recordedAt', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
