<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261019173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Give feed subscriptions a UUID and cascade orphan feed deletion safely';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE feed_user ADD uuid BINARY(16) DEFAULT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql("UPDATE feed_user SET uuid = UNHEX(REPLACE(UUID(), '-', '')) WHERE uuid IS NULL");
        $this->addSql("ALTER TABLE feed_user MODIFY uuid BINARY(16) NOT NULL COMMENT '(DC2Type:uuid)'");
        $this->addSql('CREATE UNIQUE INDEX uniq_feed_user_uuid ON feed_user (uuid)');

        $this->addSql('ALTER TABLE feed_error DROP FOREIGN KEY FK_3C51531D51A5BC03');
        $this->addSql('ALTER TABLE feed_error ADD CONSTRAINT FK_3C51531D51A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id) ON DELETE SET NULL');

        $this->addSql('ALTER TABLE feed_item_content DROP FOREIGN KEY FK_24A1905EA87D462B');
        $this->addSql('ALTER TABLE feed_item_content ADD CONSTRAINT FK_24A1905EA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_item (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE feed_item DROP FOREIGN KEY FK_1491BAF151A5BC03');
        $this->addSql('ALTER TABLE feed_item ADD CONSTRAINT FK_1491BAF151A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id) ON DELETE CASCADE');

        $this->addSql('ALTER TABLE feed_user_item DROP FOREIGN KEY FK_B3F67C1DA87D462B');
        $this->addSql('ALTER TABLE feed_user_item ADD CONSTRAINT FK_B3F67C1DA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_item (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE feed_user_item DROP FOREIGN KEY FK_B3F67C1D9A8A209');
        $this->addSql('ALTER TABLE feed_user_item ADD CONSTRAINT FK_B3F67C1D9A8A209 FOREIGN KEY (user_feed_id) REFERENCES feed_user (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feed_user_item DROP FOREIGN KEY FK_B3F67C1D9A8A209');
        $this->addSql('ALTER TABLE feed_user_item ADD CONSTRAINT FK_B3F67C1D9A8A209 FOREIGN KEY (user_feed_id) REFERENCES feed_user (id)');
        $this->addSql('ALTER TABLE feed_user_item DROP FOREIGN KEY FK_B3F67C1DA87D462B');
        $this->addSql('ALTER TABLE feed_user_item ADD CONSTRAINT FK_B3F67C1DA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_item (id)');

        $this->addSql('ALTER TABLE feed_item DROP FOREIGN KEY FK_1491BAF151A5BC03');
        $this->addSql('ALTER TABLE feed_item ADD CONSTRAINT FK_1491BAF151A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)');

        $this->addSql('ALTER TABLE feed_item_content DROP FOREIGN KEY FK_24A1905EA87D462B');
        $this->addSql('ALTER TABLE feed_item_content ADD CONSTRAINT FK_24A1905EA87D462B FOREIGN KEY (feed_item_id) REFERENCES feed_item (id)');

        $this->addSql('ALTER TABLE feed_error DROP FOREIGN KEY FK_3C51531D51A5BC03');
        $this->addSql('ALTER TABLE feed_error ADD CONSTRAINT FK_3C51531D51A5BC03 FOREIGN KEY (feed_id) REFERENCES feed (id)');

        $this->addSql('DROP INDEX uniq_feed_user_uuid ON feed_user');
        $this->addSql('ALTER TABLE feed_user DROP uuid');
    }
}
