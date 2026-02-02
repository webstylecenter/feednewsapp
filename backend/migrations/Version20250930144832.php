<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930144832 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid properties to entities for extra security';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_234044ABD17F50A6 ON feed (uuid)');
        $this->addSql('ALTER TABLE feed_category ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_26998E66D17F50A6 ON feed_category (uuid)');
        $this->addSql('ALTER TABLE feed_item ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_9F8CCE49D17F50A6 ON feed_item (uuid)');
        $this->addSql('ALTER TABLE feed_tag ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F1052B16D17F50A6 ON feed_tag (uuid)');
        $this->addSql('ALTER TABLE feed_user DROP FOREIGN KEY FK_B8EB0E7751A5BC03');
        $this->addSql('ALTER TABLE feed_user DROP FOREIGN KEY FK_B8EB0E77A76ED395');
        $this->addSql('ALTER TABLE feed_user ADD CONSTRAINT FK_D043D1EA76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feed_user ADD CONSTRAINT FK_D043D1E51A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feed_user_item ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_2D088A0BD17F50A6 ON feed_user_item (uuid)');
        $this->addSql('ALTER TABLE user ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D649D17F50A6 ON user (uuid)');
        $this->addSql('ALTER TABLE user_checklist_item ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_F26EFFA0D17F50A6 ON user_checklist_item (uuid)');
        $this->addSql('ALTER TABLE user_note ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_B53CB6DDD17F50A6 ON user_note (uuid)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_9F8CCE49D17F50A6 ON feed_item');
        $this->addSql('ALTER TABLE feed_item DROP uuid');
        $this->addSql('DROP INDEX UNIQ_F1052B16D17F50A6 ON feed_tag');
        $this->addSql('ALTER TABLE feed_tag DROP uuid');
        $this->addSql('DROP INDEX UNIQ_234044ABD17F50A6 ON feed');
        $this->addSql('ALTER TABLE feed DROP uuid');
        $this->addSql('DROP INDEX UNIQ_B53CB6DDD17F50A6 ON user_note');
        $this->addSql('ALTER TABLE user_note DROP uuid');
        $this->addSql('DROP INDEX UNIQ_26998E66D17F50A6 ON feed_category');
        $this->addSql('ALTER TABLE feed_category DROP uuid');
        $this->addSql('DROP INDEX UNIQ_2D088A0BD17F50A6 ON feed_user_item');
        $this->addSql('ALTER TABLE feed_user_item DROP uuid');
        $this->addSql('DROP INDEX UNIQ_8D93D649D17F50A6 ON user');
        $this->addSql('ALTER TABLE user DROP uuid');
        $this->addSql('ALTER TABLE feed_user DROP FOREIGN KEY FK_D043D1EA76ED395');
        $this->addSql('ALTER TABLE feed_user DROP FOREIGN KEY FK_D043D1E51A5BC03');
        $this->addSql('ALTER TABLE feed_user ADD CONSTRAINT FK_B8EB0E7751A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)');
        $this->addSql('ALTER TABLE feed_user ADD CONSTRAINT FK_B8EB0E77A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('DROP INDEX UNIQ_F26EFFA0D17F50A6 ON user_checklist_item');
        $this->addSql('ALTER TABLE user_checklist_item DROP uuid');
    }
}
