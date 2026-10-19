<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add an index for feed item GUID lookups used during YouTube feed parsing';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE INDEX IF NOT EXISTS idx_feed_item_guid ON feed_item (guid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS idx_feed_item_guid ON feed_item');
    }
}
