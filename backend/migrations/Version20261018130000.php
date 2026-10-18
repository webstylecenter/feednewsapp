<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add composite index for filtering the reader by a followed feed';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE INDEX idx_fui_user_feed_pinned_created ON feed_user_item '
            . '(user_id, user_feed_id, pinned, created_at, id)'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX idx_fui_user_feed_pinned_created ON feed_user_item');
    }
}
