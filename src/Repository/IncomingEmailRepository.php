<?php

namespace App\Repository;

use App\Entity\IncomingEmail;
use App\Entity\MailboxConfig;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class IncomingEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, IncomingEmail::class);
    }

    /** @return IncomingEmail[] */
    public function findByMailbox(MailboxConfig $mailbox, string $status = null): array
    {
        $qb = $this->createQueryBuilder('e')
            ->where('e.mailbox = :mailbox')
            ->setParameter('mailbox', $mailbox)
            ->orderBy('e.receivedAt', 'DESC');

        if ($status !== null) {
            $qb->andWhere('e.status = :status')->setParameter('status', $status);
        }

        return $qb->getQuery()->getResult();
    }

    public function findByMessageId(string $messageId): ?IncomingEmail
    {
        return $this->findOneBy(['messageId' => $messageId]);
    }

    /**
     * Fetch the most recent emails across all mailboxes belonging to the given organizations.
     *
     * @param Organization[] $orgs
     * @return IncomingEmail[]
     */
    public function findRecentForOrganizations(array $orgs, int $limit = 6): array
    {
        if (empty($orgs)) return [];

        return $this->createQueryBuilder('e')
            ->join('e.mailbox', 'mc')
            ->where('mc.organization IN (:orgs)')
            ->setParameter('orgs', $orgs)
            ->orderBy('e.receivedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
