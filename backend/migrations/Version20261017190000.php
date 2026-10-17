<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017190000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the last login IP address and user agent for the migrated FeedNews security audit data.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user ADD last_ip_address VARCHAR(45) DEFAULT NULL, ADD last_user_agent LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user DROP last_ip_address, DROP last_user_agent');
    }
}
