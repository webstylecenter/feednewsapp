<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916112447 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add entities from old Feednews into this project';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE checklist_items (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, item VARCHAR(255) NOT NULL, checked TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_DFF66E93A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE errors (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, feed_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, exception LONGTEXT NOT NULL, occurrences INT DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_3C51531DA76ED395 (user_id), INDEX IDX_3C51531D51A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_categories (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_B5DE4A745E237E06 (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_item_contents (id INT AUTO_INCREMENT NOT NULL, feed_item_id INT DEFAULT NULL, content LONGTEXT DEFAULT NULL, UNIQUE INDEX UNIQ_24A1905EA87D462B (feed_item_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feed_items (id INT AUTO_INCREMENT NOT NULL, feed_id INT DEFAULT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, guid VARCHAR(255) NOT NULL, url LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_1491BAF151A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE feeds (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(191) NOT NULL, url LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notes (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, position INT NOT NULL, content LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_11BA68CA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tags (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, name VARCHAR(255) NOT NULL, color VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_6FBC9426A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, mame VARCHAR(255) NOT NULL, hide_xframe_notice TINYINT(1) DEFAULT 0 NOT NULL, enabled TINYINT(1) DEFAULT 1 NOT NULL, is_admin TINYINT(1) DEFAULT 0 NOT NULL, avatar VARCHAR(255) NOT NULL, roles JSON NOT NULL COMMENT \'(DC2Type:json)\', password VARCHAR(255) NOT NULL, last_login DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_feed_items (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, feed_item_id INT NOT NULL, user_feed_id INT DEFAULT NULL, tag_id INT DEFAULT NULL, opened_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', viewed TINYINT(1) DEFAULT 0 NOT NULL, pinned TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B3F67C1DA76ED395 (user_id), INDEX IDX_B3F67C1DA87D462B (feed_item_id), INDEX IDX_B3F67C1D9A8A209 (user_feed_id), INDEX IDX_B3F67C1DBAD26311 (tag_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_feeds (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, feed_id INT NOT NULL, icon VARCHAR(255) DEFAULT NULL, color VARCHAR(255) DEFAULT NULL, auto_pin TINYINT(1) DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_B8EB0E77A76ED395 (user_id), INDEX IDX_B8EB0E7751A5BC03 (feed_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_settings (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, setting VARCHAR(255) NOT NULL, value VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_5C844C5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weather_forecasts (id INT AUTO_INCREMENT NOT NULL, location VARCHAR(255) NOT NULL, current_temp DOUBLE PRECISION NOT NULL, current_weather VARCHAR(255) NOT NULL, temp_today_max DOUBLE PRECISION NOT NULL, temp_today_min DOUBLE PRECISION NOT NULL, temp_in1_days_max DOUBLE PRECISION NOT NULL, temp_in1_days_min DOUBLE PRECISION NOT NULL, temp_in2_days_max DOUBLE PRECISION NOT NULL, temp_in2_days_min DOUBLE PRECISION NOT NULL, temp_in3_days_max DOUBLE PRECISION NOT NULL, temp_in3_days_min DOUBLE PRECISION NOT NULL, temp_in4_days_max DOUBLE PRECISION NOT NULL, temp_in4_days_min DOUBLE PRECISION NOT NULL, temp_in5_days_max DOUBLE PRECISION NOT NULL, temp_in5_days_min DOUBLE PRECISION NOT NULL, weather_in1_days VARCHAR(255) NOT NULL, weather_in2_days DOUBLE PRECISION NOT NULL, weather_in3_days VARCHAR(255) NOT NULL, weather_in4_days VARCHAR(255) NOT NULL, weather_in5_days VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE checklist_items ADD CONSTRAINT FK_DFF66E93A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE errors ADD CONSTRAINT FK_3C51531DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE errors ADD CONSTRAINT FK_3C51531D51A5BC03 FOREIGN KEY (feed_id) REFERENCES feeds (id)');
        $this->addSql('ALTER TABLE feed_item_contents ADD CONSTRAINT FK_24A1905EA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_items (id)');
        $this->addSql('ALTER TABLE feed_items ADD CONSTRAINT FK_1491BAF151A5BC03 FOREIGN KEY (feed_id) REFERENCES feeds (id)');
        $this->addSql('ALTER TABLE notes ADD CONSTRAINT FK_11BA68CA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE tags ADD CONSTRAINT FK_6FBC9426A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_feed_items ADD CONSTRAINT FK_B3F67C1DA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_feed_items ADD CONSTRAINT FK_B3F67C1DA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_items (id)');
        $this->addSql('ALTER TABLE user_feed_items ADD CONSTRAINT FK_B3F67C1D9A8A209 FOREIGN KEY (user_feed_id) REFERENCES user_feeds (id)');
        $this->addSql('ALTER TABLE user_feed_items ADD CONSTRAINT FK_B3F67C1DBAD26311 FOREIGN KEY (tag_id) REFERENCES tags (id)');
        $this->addSql('ALTER TABLE user_feeds ADD CONSTRAINT FK_B8EB0E77A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE user_feeds ADD CONSTRAINT FK_B8EB0E7751A5BC03 FOREIGN KEY (feed_id) REFERENCES feeds (id)');
        $this->addSql('ALTER TABLE user_settings ADD CONSTRAINT FK_5C844C5A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE checklist_items DROP FOREIGN KEY FK_DFF66E93A76ED395');
        $this->addSql('ALTER TABLE errors DROP FOREIGN KEY FK_3C51531DA76ED395');
        $this->addSql('ALTER TABLE errors DROP FOREIGN KEY FK_3C51531D51A5BC03');
        $this->addSql('ALTER TABLE feed_item_contents DROP FOREIGN KEY FK_24A1905EA87D462B');
        $this->addSql('ALTER TABLE feed_items DROP FOREIGN KEY FK_1491BAF151A5BC03');
        $this->addSql('ALTER TABLE notes DROP FOREIGN KEY FK_11BA68CA76ED395');
        $this->addSql('ALTER TABLE tags DROP FOREIGN KEY FK_6FBC9426A76ED395');
        $this->addSql('ALTER TABLE user_feed_items DROP FOREIGN KEY FK_B3F67C1DA76ED395');
        $this->addSql('ALTER TABLE user_feed_items DROP FOREIGN KEY FK_B3F67C1DA87D462B');
        $this->addSql('ALTER TABLE user_feed_items DROP FOREIGN KEY FK_B3F67C1D9A8A209');
        $this->addSql('ALTER TABLE user_feed_items DROP FOREIGN KEY FK_B3F67C1DBAD26311');
        $this->addSql('ALTER TABLE user_feeds DROP FOREIGN KEY FK_B8EB0E77A76ED395');
        $this->addSql('ALTER TABLE user_feeds DROP FOREIGN KEY FK_B8EB0E7751A5BC03');
        $this->addSql('ALTER TABLE user_settings DROP FOREIGN KEY FK_5C844C5A76ED395');
        $this->addSql('DROP TABLE checklist_items');
        $this->addSql('DROP TABLE errors');
        $this->addSql('DROP TABLE feed_categories');
        $this->addSql('DROP TABLE feed_item_contents');
        $this->addSql('DROP TABLE feed_items');
        $this->addSql('DROP TABLE feeds');
        $this->addSql('DROP TABLE notes');
        $this->addSql('DROP TABLE tags');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_feed_items');
        $this->addSql('DROP TABLE user_feeds');
        $this->addSql('DROP TABLE user_settings');
        $this->addSql('DROP TABLE weather_forecasts');
    }
}
