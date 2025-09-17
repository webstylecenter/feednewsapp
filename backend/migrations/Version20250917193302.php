<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917193302 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add created_at and updated_at to User entity';
    }

    /** @SuppressWarnings("UnusedFormalParameter") */
    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() !== 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        // stage 1: add as NULLable
        $this->addSql('ALTER TABLE user ADD created_at DATETIME DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
        // stage 2: backfill existing rows
        $this->addSql('UPDATE user SET created_at = NOW(), updated_at = NOW() WHERE created_at IS NULL OR updated_at IS NULL');
        // stage 3: enforce NOT NULL
        $this->addSql('ALTER TABLE user MODIFY created_at DATETIME NOT NULL, MODIFY updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP created_at, DROP updated_at');
    }
}
