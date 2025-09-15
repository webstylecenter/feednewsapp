<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Error;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Error>
 * @method Error|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<Error> findAll()
 * @method array<Error> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method Error|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
class ErrorRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Error::class);
    }
}
