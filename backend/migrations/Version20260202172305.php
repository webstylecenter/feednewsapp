<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260202172305 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add uuid for user settings and move forecast data to separate table';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE weather_forecast_day (id INT AUTO_INCREMENT NOT NULL, forecast_id INT NOT NULL, day_offset SMALLINT NOT NULL, temp_max DOUBLE PRECISION NOT NULL, temp_min DOUBLE PRECISION NOT NULL, weather VARCHAR(255) NOT NULL, INDEX IDX_EEA596E6F8DCC97 (forecast_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE weather_forecast_day ADD CONSTRAINT FK_EEA596E6F8DCC97 FOREIGN KEY (forecast_id) REFERENCES weather_forecast (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE user_setting ADD uuid BINARY(16) NOT NULL COMMENT \'(DC2Type:uuid)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_C779A692D17F50A6 ON user_setting (uuid)');
        $this->addSql('ALTER TABLE weather_forecast DROP temp_today_max, DROP temp_today_min, DROP temp_in1_days_max, DROP temp_in1_days_min, DROP temp_in2_days_max, DROP temp_in2_days_min, DROP temp_in3_days_max, DROP temp_in3_days_min, DROP temp_in4_days_max, DROP temp_in4_days_min, DROP temp_in5_days_max, DROP temp_in5_days_min, DROP weather_in1_days, DROP weather_in2_days, DROP weather_in3_days, DROP weather_in4_days, DROP weather_in5_days');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE weather_forecast_day DROP FOREIGN KEY FK_EEA596E6F8DCC97');
        $this->addSql('DROP TABLE weather_forecast_day');
        $this->addSql('ALTER TABLE weather_forecast ADD temp_today_max DOUBLE PRECISION NOT NULL, ADD temp_today_min DOUBLE PRECISION NOT NULL, ADD temp_in1_days_max DOUBLE PRECISION NOT NULL, ADD temp_in1_days_min DOUBLE PRECISION NOT NULL, ADD temp_in2_days_max DOUBLE PRECISION NOT NULL, ADD temp_in2_days_min DOUBLE PRECISION NOT NULL, ADD temp_in3_days_max DOUBLE PRECISION NOT NULL, ADD temp_in3_days_min DOUBLE PRECISION NOT NULL, ADD temp_in4_days_max DOUBLE PRECISION NOT NULL, ADD temp_in4_days_min DOUBLE PRECISION NOT NULL, ADD temp_in5_days_max DOUBLE PRECISION NOT NULL, ADD temp_in5_days_min DOUBLE PRECISION NOT NULL, ADD weather_in1_days VARCHAR(255) NOT NULL, ADD weather_in2_days VARCHAR(255) NOT NULL, ADD weather_in3_days VARCHAR(255) NOT NULL, ADD weather_in4_days VARCHAR(255) NOT NULL, ADD weather_in5_days VARCHAR(255) NOT NULL');
        $this->addSql('DROP INDEX UNIQ_C779A692D17F50A6 ON user_setting');
        $this->addSql('ALTER TABLE user_setting DROP uuid');
    }
}
