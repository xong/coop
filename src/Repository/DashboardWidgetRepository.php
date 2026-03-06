<?php

namespace App\Repository;

use App\Entity\DashboardWidget;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class DashboardWidgetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DashboardWidget::class);
    }

    /** @return DashboardWidget[] ordered by position */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('w')
            ->where('w.user = :user')
            ->setParameter('user', $user)
            ->orderBy('w.position', 'ASC')
            ->getQuery()->getResult();
    }
}
