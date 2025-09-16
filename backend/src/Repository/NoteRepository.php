<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Note;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Note>
 * @method Note|null find($id, $lockMode = null, $lockVersion = null)
 * @method array<Note> findAll()
 * @method array<Note> findBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null, $limit = null, $offset = null)
 * @method Note|null findOneBy(array<string, mixed> $criteria, array<string, mixed> $orderBy = null)
 */
final class NoteRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
    ) {
        parent::__construct($registry, Note::class);
    }
}
