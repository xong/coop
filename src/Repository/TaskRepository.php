<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\Project;
use App\Entity\Task;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Task::class);
    }

    public function save(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(Task $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findByOrganization(Organization $org, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.organization = :org')
            ->andWhere('t.project IS NULL')
            ->setParameter('org', $org)
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.priority', 'DESC');

        if ($status) $qb->andWhere('t.status = :status')->setParameter('status', $status);

        return $qb->getQuery()->getResult();
    }

    public function findByProject(Project $project, ?string $status = null): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.project = :project')
            ->setParameter('project', $project)
            ->orderBy('t.dueDate', 'ASC')
            ->addOrderBy('t.priority', 'DESC');

        if ($status) $qb->andWhere('t.status = :status')->setParameter('status', $status);

        return $qb->getQuery()->getResult();
    }

    public function findAssignedToUser(User $user): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.assignees', 'a')
            ->where('a = :user')
            ->andWhere('t.status != :done')
            ->setParameter('user', $user)
            ->setParameter('done', Task::STATUS_DONE)
            ->orderBy('t.dueDate', 'ASC')
            ->getQuery()->getResult();
    }

    public function findForCalendar(Organization $org, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.organization = :org')
            ->andWhere('t.dueDate BETWEEN :from AND :to')
            ->setParameter('org', $org)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->getQuery()->getResult();
    }
}
