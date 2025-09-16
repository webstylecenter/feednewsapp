<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\WeatherForecast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WeatherForecast>
 * @method WeatherForecast|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<WeatherForecast> findAll()
 * @method array<WeatherForecast> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method WeatherForecast|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class WeatherForecastRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, WeatherForecast::class);
    }
}
