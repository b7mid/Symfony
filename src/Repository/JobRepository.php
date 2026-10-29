<?php

namespace App\Repository;

use App\Entity\Job;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Job>
 */
class JobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Job::class);
    }

    /**
     * @return Job[] Returns an array of Job objects matching the search query
     */
    public function findBySearchQuery(?string $query): array
    {
        $qb = $this->createQueryBuilder('j');

        if ($query) {
            $qb->andWhere('j.title LIKE :query OR j.location LIKE :query OR j.company LIKE :query')
               ->setParameter('query', '%' . $query . '%');
        }

        return $qb->orderBy('j.createdAt', 'DESC')
                  ->getQuery()
                  ->getResult();
    }
}