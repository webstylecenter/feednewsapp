<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917182707 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Rename database tables to more domain driven name setup';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('
            RENAME TABLE
                feeds              TO feed,
                feed_categories    TO feed_category,
                errors             TO feed_error,
                feed_items         TO feed_item,
                feed_item_contents TO feed_item_content,
                tags               TO feed_tag,
                user_feeds         TO feed_user,
                user_feed_items    TO feed_user_item,
                weather_forecasts  TO weather_forecast,
                notes              TO user_note,
                checklist_items    TO user_checklist_item,
                user_settings      TO user_setting
        ');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('
            RENAME TABLE
                feed               TO feeds,
                feed_category      TO feed_categories,
                feed_error         TO errors,
                feed_item          TO feed_items,
                feed_item_content  TO feed_item_contents,
                feed_tag           TO tags,
                feed_user          TO user_feeds,
                feed_user_item     TO user_feed_items,
                weather_forecast   TO weather_forecasts,
                user_note          TO notes,
                user_checklist_item TO checklist_items,
                user_setting       TO user_settings
        ');
    }
}
