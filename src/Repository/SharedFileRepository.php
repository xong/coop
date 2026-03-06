<?php

namespace App\Repository;

use App\Entity\FileCategory;
use App\Entity\Organization;
use App\Entity\Project;
use App\Entity\SharedFile;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SharedFileRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SharedFile::class);
    }

    public function save(SharedFile $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(SharedFile $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findByOrganization(Organization $org, ?FileCategory $category = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.organization = :org')
            ->andWhere('f.project IS NULL')
            ->setParameter('org', $org)
            ->orderBy('f.createdAt', 'DESC');

        if ($category) {
            $qb->andWhere('f.category = :cat')->setParameter('cat', $category);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByProject(Project $project, ?FileCategory $category = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->where('f.project = :project')
            ->setParameter('project', $project)
            ->orderBy('f.createdAt', 'DESC');

        if ($category) {
            $qb->andWhere('f.category = :cat')->setParameter('cat', $category);
        }

        return $qb->getQuery()->getResult();
    }
}
