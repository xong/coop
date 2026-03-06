<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305223441 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE topic (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, last_activity_at DATETIME DEFAULT NULL, is_pinned TINYINT NOT NULL, is_closed TINYINT NOT NULL, organization_id INT NOT NULL, project_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_9D40DE1B32C8A3DE (organization_id), INDEX IDX_9D40DE1B166D1F9C (project_id), INDEX IDX_9D40DE1BB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE topic_post (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, topic_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_62610D381F55203D (topic_id), INDEX IDX_62610D38F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE topic_post_attachment (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, storage_path VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, post_id INT NOT NULL, INDEX IDX_A4953A0B4B89032C (post_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1B166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE topic ADD CONSTRAINT FK_9D40DE1BB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE topic_post ADD CONSTRAINT FK_62610D381F55203D FOREIGN KEY (topic_id) REFERENCES topic (id)');
        $this->addSql('ALTER TABLE topic_post ADD CONSTRAINT FK_62610D38F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE topic_post_attachment ADD CONSTRAINT FK_A4953A0B4B89032C FOREIGN KEY (post_id) REFERENCES topic_post (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1B32C8A3DE');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1B166D1F9C');
        $this->addSql('ALTER TABLE topic DROP FOREIGN KEY FK_9D40DE1BB03A8386');
        $this->addSql('ALTER TABLE topic_post DROP FOREIGN KEY FK_62610D381F55203D');
        $this->addSql('ALTER TABLE topic_post DROP FOREIGN KEY FK_62610D38F675F31B');
        $this->addSql('ALTER TABLE topic_post_attachment DROP FOREIGN KEY FK_A4953A0B4B89032C');
        $this->addSql('DROP TABLE topic');
        $this->addSql('DROP TABLE topic_post');
        $this->addSql('DROP TABLE topic_post_attachment');
    }
}
