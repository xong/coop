<?php

namespace App\Repository;

use App\Entity\Contact;
use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    /** @return Contact[] */
    public function search(Organization $org, string $query = ''): array
    {
        $qb = $this->createQueryBuilder('c')
            ->where('c.organization = :org')
            ->setParameter('org', $org)
            ->orderBy('c.lastName', 'ASC')
            ->addOrderBy('c.firstName', 'ASC');

        if ($query !== '') {
            $qb->andWhere(
                'c.firstName LIKE :q OR c.lastName LIKE :q OR c.email LIKE :q OR c.company LIKE :q'
            )->setParameter('q', '%' . $query . '%');
        }

        return $qb->getQuery()->getResult();
    }
}
