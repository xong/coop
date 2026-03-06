<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Project;
use App\Entity\Topic;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopicRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Topic::class);
    }

    public function save(Topic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(Topic $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findByOrganization(Organization $org): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.organization = :org')
            ->andWhere('t.project IS NULL')
            ->setParameter('org', $org)
            ->orderBy('t.isPinned', 'DESC')
            ->addOrderBy('t.lastActivityAt', 'DESC')
            ->getQuery()->getResult();
    }

    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.isPinned', 'DESC')
            ->addOrderBy('t.lastActivityAt', 'DESC')
            ->getQuery()->getResult();
    }
}
