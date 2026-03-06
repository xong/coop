<?php

namespace App\Repository;

use App\Entity\NotificationSetting;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NotificationSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationSetting::class);
    }

    /** @return array<string, NotificationSetting> keyed by eventType */
    public function findMapForUser(User $user): array
    {
        $settings = $this->findBy(['user' => $user]);
        $map = [];
        foreach ($settings as $s) {
            $map[$s->getEventType()] = $s;
        }
        return $map;
    }
}
