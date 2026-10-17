<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261017215637 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX uniq_feed_user_user_feed ON feed_user');
        $this->addSql('DROP INDEX uniq_fui_user_feeditem ON feed_user_item');
        $this->addSql('ALTER TABLE user CHANGE avatar avatar LONGTEXT DEFAULT NULL');
        $this->addSql('DROP INDEX uniq_user_setting_user_key ON user_setting');
        $this->addSql('DROP INDEX uniq_weather_forecast_day ON weather_forecast_day');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_user_user_feed ON feed_user (user_id, feed_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_fui_user_feeditem ON feed_user_item (user_id, feed_item_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_weather_forecast_day ON weather_forecast_day (forecast_id, day_offset)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_setting_user_key ON user_setting (user_id, setting)');
        $this->addSql('ALTER TABLE user CHANGE avatar avatar VARCHAR(255) DEFAULT NULL');
    }
}
