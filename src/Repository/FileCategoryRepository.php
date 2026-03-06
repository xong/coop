<?php

namespace App\Repository;

use App\Entity\FileCategory;
use App\Entity\Organization;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FileCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileCategory::class);
    }

    public function save(FileCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(FileCategory $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findRootByOrganization(Organization $org): array
    {
        return $this->createQueryBuilder('fc')
            ->where('fc.organization = :org')
            ->andWhere('fc.parent IS NULL')
            ->andWhere('fc.project IS NULL')
            ->setParameter('org', $org)
            ->orderBy('fc.name', 'ASC')
            ->getQuery()->getResult();
    }

    public function findRootByProject(Project $project): array
    {
        return $this->createQueryBuilder('fc')
            ->where('fc.project = :project')
            ->andWhere('fc.parent IS NULL')
            ->setParameter('project', $project)
            ->orderBy('fc.name', 'ASC')
            ->getQuery()->getResult();
    }
}
