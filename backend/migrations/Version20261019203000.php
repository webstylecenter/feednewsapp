<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019203000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Restore unique constraints that were dropped while Doctrine 3 table attributes were ignored';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_weather_forecast_day '
            . 'ON weather_forecast_day (forecast_id, day_offset)',
        );
        $this->addSql(
            'CREATE UNIQUE INDEX IF NOT EXISTS uniq_user_setting_user_key '
            . 'ON user_setting (user_id, setting)',
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IF EXISTS uniq_user_setting_user_key ON user_setting');
        $this->addSql('DROP INDEX IF EXISTS uniq_weather_forecast_day ON weather_forecast_day');
    }
}
