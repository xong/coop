<?php

namespace App\Repository;

use App\Entity\ProjectInvitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ProjectInvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProjectInvitation::class);
    }

    public function save(ProjectInvitation $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findByToken(string $token): ?ProjectInvitation
    {
        return $this->findOneBy(['token' => $token]);
    }
}
