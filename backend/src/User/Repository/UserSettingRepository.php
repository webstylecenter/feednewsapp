<?php

declare(strict_types=1);

namespace App\User\Repository;

use App\User\Entity\UserSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserSetting>
 * @method UserSetting|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<UserSetting> findAll()
 * @method array<UserSetting> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method UserSetting|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class UserSettingRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, UserSetting::class);
    }
}
