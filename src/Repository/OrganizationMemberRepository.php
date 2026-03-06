<?php

namespace App\Repository;

use App\Entity\OrganizationMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationMember::class);
    }

    public function save(OrganizationMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(OrganizationMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
