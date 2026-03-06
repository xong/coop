<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Project;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Project::class);
    }

    public function save(Project $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findVisibleByOrganization(Organization $organization, User $user): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.organization = :org')
            ->andWhere(
                'p.isPublic = true OR EXISTS (SELECT pm FROM App\Entity\ProjectMember pm WHERE pm.project = p AND pm.user = :user)'
            )
            ->setParameter('org', $organization)
            ->setParameter('user', $user)
            ->orderBy('p.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneByOrganizationAndSlug(Organization $organization, string $slug): ?Project
    {
        return $this->findOneBy(['organization' => $organization, 'slug' => $slug]);
    }
}
