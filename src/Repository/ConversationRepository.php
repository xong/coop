<?php

namespace App\Repository;

use App\Entity\Conversation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conversation::class);
    }

    /** @return Conversation[] All conversations for a user, newest first */
    public function findForUser(User $user): array
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->orderBy('c.lastMessageAt', 'DESC')
            ->addOrderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /** Find existing direct conversation between exactly two users */
    public function findDirectBetween(User $a, User $b): ?Conversation
    {
        return $this->createQueryBuilder('c')
            ->join('c.participants', 'p1')
            ->join('c.participants', 'p2')
            ->where('c.type = :type')
            ->andWhere('p1.user = :a')
            ->andWhere('p2.user = :b')
            ->setParameter('type', Conversation::TYPE_DIRECT)
            ->setParameter('a', $a)
            ->setParameter('b', $b)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function countUnreadForUser(User $user): int
    {
        $conversations = $this->findForUser($user);
        $total = 0;
        foreach ($conversations as $conv) {
            $total += $conv->getUnreadCount($user);
        }
        return $total;
    }
}
