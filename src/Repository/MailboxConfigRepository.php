<?php

namespace App\Repository;

use App\Entity\MailboxConfig;
use App\Entity\Organization;
use App\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class MailboxConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailboxConfig::class);
    }

    /** @return MailboxConfig[] */
    public function findByOrganization(Organization $org): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.organization = :org')
            ->setParameter('org', $org)
            ->orderBy('m.name', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return MailboxConfig[] */
    public function findByProject(Project $project): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.project = :project')
            ->setParameter('project', $project)
            ->orderBy('m.name', 'ASC')
            ->getQuery()->getResult();
    }

    /** @return MailboxConfig[] */
    public function findActiveForSync(): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.isActive = true')
            ->getQuery()->getResult();
    }
}
