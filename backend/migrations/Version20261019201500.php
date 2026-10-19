<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019201500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Repair feed indexes and unique constraints that may be missing after a manual schema diff';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_fui_user_created_feed '
            . 'ON feed_user_item (user_id, created_at, user_feed_id)',
        );
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_fui_user_pinned_created '
            . 'ON feed_user_item (user_id, pinned, created_at, id)',
        );
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_fui_user_feed_pinned_created '
            . 'ON feed_user_item (user_id, user_feed_id, pinned, created_at, id)',
        );
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_fui_user_tag ON feed_user_item (user_id, tag_id)');
        $this->addSql(
            'CREATE INDEX IF NOT EXISTS idx_fui_user_opened '
            . 'ON feed_user_item (user_id, opened_at, id)',
        );

        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_fui_user_feeditem '
            . 'ON feed_user_item (user_id, feed_item_id)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_feed_item_feed_guid '
            . 'ON feed_item (feed_id, guid)',
        );
        $this->addSql('CREATE UNIQUE INDEX IF NOT EXISTS uniq_feed_user_uuid ON feed_user (uuid)');
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_feed_user_user_feed '
            . 'ON feed_user (user_id, feed_id)',
        );
    }

    public function down(Schema $schema): void
    {
        // This migration only repairs schema objects that should already exist according to the entity mappings.
    }
}
