<?php

namespace App\Repository;

use App\Entity\TopicPostAttachment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopicPostAttachmentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopicPostAttachment::class);
    }

    public function save(TopicPostAttachment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }
}
