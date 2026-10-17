<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\User;
use App\User\Entity\UserSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<UserSetting> */
final class UserSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserSetting::class);
    }

    /** @return array<string, string|null> */
    public function valuesForUser(User $user): array
    {
        $values = [];
        foreach ($this->findBy(['user' => $user]) as $setting) {
            $values[$setting->getSetting()] = $setting->getValue();
        }

        return $values;
    }

    public function findForUserAndKey(User $user, string $key): ?UserSetting
    {
        return $this->findOneBy(['user' => $user, 'setting' => $key]);
    }
}
