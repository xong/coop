<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305225352 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE email_comments (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, email_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_54F88963A832C1C9 (email_id), INDEX IDX_54F88963F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE incoming_emails (id INT AUTO_INCREMENT NOT NULL, message_id VARCHAR(255) NOT NULL, subject VARCHAR(255) NOT NULL, from_email VARCHAR(255) NOT NULL, from_name VARCHAR(255) NOT NULL, to_addresses LONGTEXT DEFAULT NULL, cc_addresses LONGTEXT DEFAULT NULL, body_text LONGTEXT NOT NULL, body_html LONGTEXT DEFAULT NULL, attachments JSON NOT NULL, received_at DATETIME NOT NULL, is_read TINYINT NOT NULL, status VARCHAR(30) NOT NULL, in_reply_to VARCHAR(255) DEFAULT NULL, mailbox_id INT NOT NULL, assigned_to_id INT DEFAULT NULL, linked_topic_id INT DEFAULT NULL, linked_project_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_9DC51995537A1329 (message_id), INDEX IDX_9DC5199566EC35CC (mailbox_id), INDEX IDX_9DC51995F4BD7827 (assigned_to_id), INDEX IDX_9DC5199571556EE2 (linked_topic_id), INDEX IDX_9DC5199581A9C4DB (linked_project_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE mailbox_configs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, imap_host VARCHAR(255) NOT NULL, imap_port INT NOT NULL, imap_encryption VARCHAR(20) NOT NULL, imap_username VARCHAR(255) NOT NULL, imap_password VARCHAR(255) NOT NULL, imap_folder VARCHAR(100) NOT NULL, smtp_host VARCHAR(255) NOT NULL, smtp_port INT NOT NULL, smtp_encryption VARCHAR(20) NOT NULL, smtp_username VARCHAR(255) NOT NULL, smtp_password VARCHAR(255) NOT NULL, from_email VARCHAR(255) NOT NULL, from_name VARCHAR(150) NOT NULL, is_active TINYINT NOT NULL, last_sync_at DATETIME DEFAULT NULL, last_sync_error VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, organization_id INT DEFAULT NULL, project_id INT DEFAULT NULL, created_by_id INT DEFAULT NULL, INDEX IDX_EC23FC8632C8A3DE (organization_id), INDEX IDX_EC23FC86166D1F9C (project_id), INDEX IDX_EC23FC86B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE email_comments ADD CONSTRAINT FK_54F88963A832C1C9 FOREIGN KEY (email_id) REFERENCES incoming_emails (id)');
        $this->addSql('ALTER TABLE email_comments ADD CONSTRAINT FK_54F88963F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE incoming_emails ADD CONSTRAINT FK_9DC5199566EC35CC FOREIGN KEY (mailbox_id) REFERENCES mailbox_configs (id)');
        $this->addSql('ALTER TABLE incoming_emails ADD CONSTRAINT FK_9DC51995F4BD7827 FOREIGN KEY (assigned_to_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE incoming_emails ADD CONSTRAINT FK_9DC5199571556EE2 FOREIGN KEY (linked_topic_id) REFERENCES topic (id)');
        $this->addSql('ALTER TABLE incoming_emails ADD CONSTRAINT FK_9DC5199581A9C4DB FOREIGN KEY (linked_project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE mailbox_configs ADD CONSTRAINT FK_EC23FC8632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE mailbox_configs ADD CONSTRAINT FK_EC23FC86166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE mailbox_configs ADD CONSTRAINT FK_EC23FC86B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE email_comments DROP FOREIGN KEY FK_54F88963A832C1C9');
        $this->addSql('ALTER TABLE email_comments DROP FOREIGN KEY FK_54F88963F675F31B');
        $this->addSql('ALTER TABLE incoming_emails DROP FOREIGN KEY FK_9DC5199566EC35CC');
        $this->addSql('ALTER TABLE incoming_emails DROP FOREIGN KEY FK_9DC51995F4BD7827');
        $this->addSql('ALTER TABLE incoming_emails DROP FOREIGN KEY FK_9DC5199571556EE2');
        $this->addSql('ALTER TABLE incoming_emails DROP FOREIGN KEY FK_9DC5199581A9C4DB');
        $this->addSql('ALTER TABLE mailbox_configs DROP FOREIGN KEY FK_EC23FC8632C8A3DE');
        $this->addSql('ALTER TABLE mailbox_configs DROP FOREIGN KEY FK_EC23FC86166D1F9C');
        $this->addSql('ALTER TABLE mailbox_configs DROP FOREIGN KEY FK_EC23FC86B03A8386');
        $this->addSql('DROP TABLE email_comments');
        $this->addSql('DROP TABLE incoming_emails');
        $this->addSql('DROP TABLE mailbox_configs');
    }
}
