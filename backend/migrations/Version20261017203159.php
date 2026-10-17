<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261017203159 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed_item_content DROP FOREIGN KEY FK_24A1905EA87D462B');
        $this->addSql('DROP TABLE feed_item_content');
        $this->addSql('DROP INDEX uniq_feed_user_user_feed ON feed_user');
        $this->addSql('DROP INDEX uniq_fui_user_feeditem ON feed_user_item');
        $this->addSql('DROP INDEX uniq_user_setting_user_key ON user_setting');
        $this->addSql('DROP INDEX uniq_weather_forecast_day ON weather_forecast_day');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE feed_item_content (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT NOT NULL, content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, UNIQUE INDEX UNIQ_1A5988A6A87D462B (feed_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE feed_item_content ADD CONSTRAINT FK_24A1905EA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_item (id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_user_user_feed ON feed_user (user_id, feed_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_fui_user_feeditem ON feed_user_item (user_id, feed_item_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_weather_forecast_day ON weather_forecast_day (forecast_id, day_offset)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_setting_user_key ON user_setting (user_id, setting)');
    }
}
