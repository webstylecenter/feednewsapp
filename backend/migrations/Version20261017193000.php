<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261017193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align FeedNews constraints and user setting storage with the final Symfony domain model.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE user_setting MODIFY value LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE feed_tag MODIFY user_id INT NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_user_user_feed ON feed_user (user_id, feed_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_fui_user_feeditem ON feed_user_item (user_id, feed_item_id)');
        $this->addSql('CREATE UNIQUE INDEX uniq_weather_forecast_day ON weather_forecast_day (forecast_id, day_offset)');
        $this->addSql('CREATE UNIQUE INDEX uniq_user_setting_user_key ON user_setting (user_id, setting)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX uniq_user_setting_user_key ON user_setting');
        $this->addSql('DROP INDEX uniq_weather_forecast_day ON weather_forecast_day');
        $this->addSql('DROP INDEX uniq_fui_user_feeditem ON feed_user_item');
        $this->addSql('DROP INDEX uniq_feed_user_user_feed ON feed_user');
        $this->addSql('ALTER TABLE feed_tag MODIFY user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE user_setting MODIFY value VARCHAR(255) DEFAULT NULL');
    }
}
