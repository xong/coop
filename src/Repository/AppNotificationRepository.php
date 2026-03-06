<?php

namespace App\Repository;

use App\Entity\AppNotification;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AppNotificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AppNotification::class);
    }

    /** @return AppNotification[] */
    public function findForUser(User $user, int $limit = 30): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->orderBy('n.createdAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()->getResult();
    }

    public function countUnreadForUser(User $user): int
    {
        return (int) $this->createQueryBuilder('n')
            ->select('COUNT(n.id)')
            ->where('n.user = :user')
            ->andWhere('n.isRead = false')
            ->setParameter('user', $user)
            ->getQuery()->getSingleScalarResult();
    }

    public function markAllReadForUser(User $user): void
    {
        $this->createQueryBuilder('n')
            ->update()
            ->set('n.isRead', true)
            ->where('n.user = :user')
            ->setParameter('user', $user)
            ->getQuery()->execute();
    }
}
