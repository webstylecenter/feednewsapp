<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019200000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace the feed item date index with a covering index for dashboard statistics';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fui_user_created ON feed_user_item');
        $this->addSql(
            'CREATE INDEX idx_fui_user_created_feed ON feed_user_item (user_id, created_at, user_feed_id)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fui_user_created_feed ON feed_user_item');
        $this->addSql('CREATE INDEX idx_fui_user_created ON feed_user_item (user_id, created_at)');
    }
}
