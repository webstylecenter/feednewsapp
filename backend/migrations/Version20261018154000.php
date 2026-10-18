<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018154000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an index for per-user feed item statistics by creation date';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_fui_user_created ON feed_user_item (user_id, created_at)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fui_user_created ON feed_user_item');
    }
}
