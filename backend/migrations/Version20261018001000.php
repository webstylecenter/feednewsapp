<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018001000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite indexes for feed reader, tag counts and history queries';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX idx_fui_user_pinned_created ON feed_user_item (user_id, pinned, created_at, id)');
        $this->addSql('CREATE INDEX idx_fui_user_tag ON feed_user_item (user_id, tag_id)');
        $this->addSql('CREATE INDEX idx_fui_user_opened ON feed_user_item (user_id, opened_at, id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fui_user_pinned_created ON feed_user_item');
        $this->addSql('DROP INDEX idx_fui_user_tag ON feed_user_item');
        $this->addSql('DROP INDEX idx_fui_user_opened ON feed_user_item');
    }
}
