<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261018173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add HTTP feed validators and enforce one GUID per feed for faster duplicate-safe feed updates';
    }

    public function up(Schema $schema): void
    {
        $duplicate = $this->connection->fetchOne(
            'SELECT 1 FROM feed_item '
            . 'WHERE feed_id IS NOT NULL '
            . 'GROUP BY feed_id, guid '
            . 'HAVING COUNT(*) > 1 '
            . 'LIMIT 1'
        );

        $this->abortIf(
            $duplicate !== false,
            'Duplicate feed_item rows exist for the same feed and GUID. Resolve them before running this migration.',
        );

        $this->addSql(
            'ALTER TABLE feed '
            . 'ADD http_etag LONGTEXT DEFAULT NULL, '
            . 'ADD http_last_modified VARCHAR(255) DEFAULT NULL'
        );
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_item_feed_guid ON feed_item (feed_id, guid)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_feed_item_feed_guid ON feed_item');
        $this->addSql('ALTER TABLE feed DROP http_etag, DROP http_last_modified');
    }
}
