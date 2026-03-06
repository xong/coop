<?php

namespace App\Repository;

use App\Entity\CalendarEvent;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CalendarEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CalendarEvent::class);
    }

    public function save(CalendarEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function remove(CalendarEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) $this->getEntityManager()->flush();
    }

    public function findForCalendar(Organization $org, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organization = :org')
            ->andWhere('e.startAt BETWEEN :from AND :to OR (e.startAt <= :from AND e.endAt >= :from)')
            ->setParameter('org', $org)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('e.startAt', 'ASC')
            ->getQuery()->getResult();
    }

    public function findByOrganization(Organization $org): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.organization = :org')
            ->setParameter('org', $org)
            ->orderBy('e.startAt', 'DESC')
            ->getQuery()->getResult();
    }
}
