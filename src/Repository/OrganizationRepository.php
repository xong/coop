<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    public function save(Organization $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByMember(User $user): array
    {
        return $this->createQueryBuilder('o')
            ->join('o.members', 'm')
            ->where('m.user = :user')
            ->setParameter('user', $user)
            ->orderBy('o.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneBySlug(string $slug): ?Organization
    {
        return $this->findOneBy(['slug' => $slug]);
    }
}
