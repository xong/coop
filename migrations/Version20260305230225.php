<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305230225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE contact_comments (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, contact_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_C5BB0ABDE7A1254A (contact_id), INDEX IDX_C5BB0ABDF675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_fields (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, value VARCHAR(500) NOT NULL, contact_id INT NOT NULL, INDEX IDX_EE77D409E7A1254A (contact_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contacts (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(50) DEFAULT NULL, company VARCHAR(150) DEFAULT NULL, position VARCHAR(150) DEFAULT NULL, website VARCHAR(255) DEFAULT NULL, address LONGTEXT DEFAULT NULL, notes LONGTEXT DEFAULT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, organization_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_3340157332C8A3DE (organization_id), INDEX IDX_33401573B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_projects (contact_id INT NOT NULL, project_id INT NOT NULL, INDEX IDX_C6B62F33E7A1254A (contact_id), INDEX IDX_C6B62F33166D1F9C (project_id), PRIMARY KEY (contact_id, project_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE contact_topics (contact_id INT NOT NULL, topic_id INT NOT NULL, INDEX IDX_16471B8E7A1254A (contact_id), INDEX IDX_16471B81F55203D (topic_id), PRIMARY KEY (contact_id, topic_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE contact_comments ADD CONSTRAINT FK_C5BB0ABDE7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id)');
        $this->addSql('ALTER TABLE contact_comments ADD CONSTRAINT FK_C5BB0ABDF675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contact_fields ADD CONSTRAINT FK_EE77D409E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id)');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_3340157332C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE contacts ADD CONSTRAINT FK_33401573B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE contact_projects ADD CONSTRAINT FK_C6B62F33E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_projects ADD CONSTRAINT FK_C6B62F33166D1F9C FOREIGN KEY (project_id) REFERENCES project (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_topics ADD CONSTRAINT FK_16471B8E7A1254A FOREIGN KEY (contact_id) REFERENCES contacts (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE contact_topics ADD CONSTRAINT FK_16471B81F55203D FOREIGN KEY (topic_id) REFERENCES topic (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE contact_comments DROP FOREIGN KEY FK_C5BB0ABDE7A1254A');
        $this->addSql('ALTER TABLE contact_comments DROP FOREIGN KEY FK_C5BB0ABDF675F31B');
        $this->addSql('ALTER TABLE contact_fields DROP FOREIGN KEY FK_EE77D409E7A1254A');
        $this->addSql('ALTER TABLE contacts DROP FOREIGN KEY FK_3340157332C8A3DE');
        $this->addSql('ALTER TABLE contacts DROP FOREIGN KEY FK_33401573B03A8386');
        $this->addSql('ALTER TABLE contact_projects DROP FOREIGN KEY FK_C6B62F33E7A1254A');
        $this->addSql('ALTER TABLE contact_projects DROP FOREIGN KEY FK_C6B62F33166D1F9C');
        $this->addSql('ALTER TABLE contact_topics DROP FOREIGN KEY FK_16471B8E7A1254A');
        $this->addSql('ALTER TABLE contact_topics DROP FOREIGN KEY FK_16471B81F55203D');
        $this->addSql('DROP TABLE contact_comments');
        $this->addSql('DROP TABLE contact_fields');
        $this->addSql('DROP TABLE contacts');
        $this->addSql('DROP TABLE contact_projects');
        $this->addSql('DROP TABLE contact_topics');
    }
}
