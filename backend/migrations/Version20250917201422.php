<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917201422 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Fixed not nulls for UserNote and FeedItemContent';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed_item_content CHANGE feed_item_id feed_item_id INT NOT NULL');
        $this->addSql('ALTER TABLE user_note CHANGE user_id user_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed_item_content CHANGE feed_item_id feed_item_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_note CHANGE user_id user_id INT DEFAULT NULL');
    }
}
