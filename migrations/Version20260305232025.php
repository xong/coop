<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305232025 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE conversation_participants (id INT AUTO_INCREMENT NOT NULL, joined_at DATETIME NOT NULL, last_read_at DATETIME DEFAULT NULL, conversation_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_21821ED39AC0396 (conversation_id), INDEX IDX_21821ED3A76ED395 (user_id), UNIQUE INDEX UNIQ_21821ED39AC0396A76ED395 (conversation_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE conversations (id INT AUTO_INCREMENT NOT NULL, type VARCHAR(20) NOT NULL, title VARCHAR(150) DEFAULT NULL, created_at DATETIME NOT NULL, last_message_at DATETIME DEFAULT NULL, organization_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_C2521BF132C8A3DE (organization_id), INDEX IDX_C2521BF1B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE direct_messages (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, is_deleted TINYINT NOT NULL, conversation_id INT NOT NULL, sender_id INT NOT NULL, INDEX IDX_721C1B5A9AC0396 (conversation_id), INDEX IDX_721C1B5AF624B39D (sender_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED39AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id)');
        $this->addSql('ALTER TABLE conversation_participants ADD CONSTRAINT FK_21821ED3A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF132C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE conversations ADD CONSTRAINT FK_C2521BF1B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE direct_messages ADD CONSTRAINT FK_721C1B5A9AC0396 FOREIGN KEY (conversation_id) REFERENCES conversations (id)');
        $this->addSql('ALTER TABLE direct_messages ADD CONSTRAINT FK_721C1B5AF624B39D FOREIGN KEY (sender_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED39AC0396');
        $this->addSql('ALTER TABLE conversation_participants DROP FOREIGN KEY FK_21821ED3A76ED395');
        $this->addSql('ALTER TABLE conversations DROP FOREIGN KEY FK_C2521BF132C8A3DE');
        $this->addSql('ALTER TABLE conversations DROP FOREIGN KEY FK_C2521BF1B03A8386');
        $this->addSql('ALTER TABLE direct_messages DROP FOREIGN KEY FK_721C1B5A9AC0396');
        $this->addSql('ALTER TABLE direct_messages DROP FOREIGN KEY FK_721C1B5AF624B39D');
        $this->addSql('DROP TABLE conversation_participants');
        $this->addSql('DROP TABLE conversations');
        $this->addSql('DROP TABLE direct_messages');
    }
}
