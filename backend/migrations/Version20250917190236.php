<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250917190236 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add indexes to make sure duplicate entries cannot be created';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed RENAME INDEX idx_5a29f52f12469de2 TO IDX_234044AB12469DE2');
        $this->addSql('ALTER TABLE feed_category RENAME INDEX uniq_b5de4a745e237e06 TO UNIQ_26998E665E237E06');
        $this->addSql('ALTER TABLE feed_error RENAME INDEX idx_3c51531da76ed395 TO IDX_A852EFCDA76ED395');
        $this->addSql('ALTER TABLE feed_error RENAME INDEX idx_3c51531d51a5bc03 TO IDX_A852EFCD51A5BC03');
        $this->addSql('ALTER TABLE feed_item RENAME INDEX idx_1491baf151a5bc03 TO IDX_9F8CCE4951A5BC03');
        $this->addSql('ALTER TABLE feed_item_content RENAME INDEX uniq_24a1905ea87d462b TO UNIQ_1A5988A6A87D462B');
        $this->addSql('ALTER TABLE feed_tag CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE feed_tag RENAME INDEX idx_6fbc9426a76ed395 TO IDX_F1052B16A76ED395');
        $this->addSql('ALTER TABLE feed_user RENAME INDEX idx_b8eb0e77a76ed395 TO IDX_D043D1EA76ED395');
        $this->addSql('ALTER TABLE feed_user RENAME INDEX idx_b8eb0e7751a5bc03 TO IDX_D043D1E51A5BC03');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_b3f67c1da76ed395 TO IDX_2D088A0BA76ED395');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_b3f67c1da87d462b TO IDX_2D088A0BA87D462B');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_b3f67c1d9a8a209 TO IDX_2D088A0B9A8A209');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_b3f67c1dbad26311 TO IDX_2D088A0BBAD26311');
        $this->addSql('ALTER TABLE user_checklist_item RENAME INDEX idx_dff66e93a76ed395 TO IDX_F26EFFA0A76ED395');
        $this->addSql('ALTER TABLE user_note RENAME INDEX idx_11ba68ca76ed395 TO IDX_B53CB6DDA76ED395');
        $this->addSql('ALTER TABLE user_setting RENAME INDEX idx_5c844c5a76ed395 TO IDX_C779A692A76ED395');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_2d088a0ba87d462b TO IDX_B3F67C1DA87D462B');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_2d088a0b9a8a209 TO IDX_B3F67C1D9A8A209');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_2d088a0bbad26311 TO IDX_B3F67C1DBAD26311');
        $this->addSql('ALTER TABLE feed_user_item RENAME INDEX idx_2d088a0ba76ed395 TO IDX_B3F67C1DA76ED395');
        $this->addSql('ALTER TABLE feed_category RENAME INDEX uniq_26998e665e237e06 TO UNIQ_B5DE4A745E237E06');
        $this->addSql('ALTER TABLE user_checklist_item RENAME INDEX idx_f26effa0a76ed395 TO IDX_DFF66E93A76ED395');
        $this->addSql('ALTER TABLE feed_item RENAME INDEX idx_9f8cce4951a5bc03 TO IDX_1491BAF151A5BC03');
        $this->addSql('ALTER TABLE feed_item_content RENAME INDEX uniq_1a5988a6a87d462b TO UNIQ_24A1905EA87D462B');
        $this->addSql('ALTER TABLE feed_error RENAME INDEX idx_a852efcda76ed395 TO IDX_3C51531DA76ED395');
        $this->addSql('ALTER TABLE feed_error RENAME INDEX idx_a852efcd51a5bc03 TO IDX_3C51531D51A5BC03');
        $this->addSql('ALTER TABLE user_note RENAME INDEX idx_b53cb6dda76ed395 TO IDX_11BA68CA76ED395');
        $this->addSql('ALTER TABLE user_setting RENAME INDEX idx_c779a692a76ed395 TO IDX_5C844C5A76ED395');
        $this->addSql('ALTER TABLE feed_tag CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE feed_tag RENAME INDEX idx_f1052b16a76ed395 TO IDX_6FBC9426A76ED395');
        $this->addSql('ALTER TABLE feed RENAME INDEX idx_234044ab12469de2 TO IDX_5A29F52F12469DE2');
        $this->addSql('ALTER TABLE feed_user RENAME INDEX idx_d043d1ea76ed395 TO IDX_B8EB0E77A76ED395');
        $this->addSql('ALTER TABLE feed_user RENAME INDEX idx_d043d1e51a5bc03 TO IDX_B8EB0E7751A5BC03');
    }
}
