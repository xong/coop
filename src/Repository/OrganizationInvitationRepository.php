<?php

namespace App\Repository;

use App\Entity\OrganizationInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OrganizationInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OrganizationInvitation::class);
    }

    public function save(OrganizationInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByToken(string $token): ?OrganizationInvitation
    {
        return $this->findOneBy(['token' => $token]);
    }
}
