<?php

namespace App\Repository;

use App\Entity\TopicPost;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TopicPostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TopicPost::class);
    }

    public function save(TopicPost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(TopicPost $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }
}
