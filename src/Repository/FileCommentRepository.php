<?php

namespace App\Repository;

use App\Entity\FileComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FileCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FileComment::class);
    }

    public function save(FileComment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(FileComment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }
}
