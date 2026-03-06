<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305222948 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE file_category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, organization_id INT DEFAULT NULL, project_id INT DEFAULT NULL, parent_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_B71C965C32C8A3DE (organization_id), INDEX IDX_B71C965C166D1F9C (project_id), INDEX IDX_B71C965C727ACA70 (parent_id), INDEX IDX_B71C965CB03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE file_comment (id INT AUTO_INCREMENT NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, file_id INT NOT NULL, author_id INT NOT NULL, INDEX IDX_CF12EB5793CB796C (file_id), INDEX IDX_CF12EB57F675F31B (author_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE shared_file (id INT AUTO_INCREMENT NOT NULL, original_name VARCHAR(255) NOT NULL, storage_path VARCHAR(255) NOT NULL, mime_type VARCHAR(100) NOT NULL, file_size INT NOT NULL, description LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, category_id INT DEFAULT NULL, organization_id INT DEFAULT NULL, project_id INT DEFAULT NULL, uploaded_by_id INT NOT NULL, INDEX IDX_36695D8812469DE2 (category_id), INDEX IDX_36695D8832C8A3DE (organization_id), INDEX IDX_36695D88166D1F9C (project_id), INDEX IDX_36695D88A2B28FE8 (uploaded_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE file_category ADD CONSTRAINT FK_B71C965C32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE file_category ADD CONSTRAINT FK_B71C965C166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE file_category ADD CONSTRAINT FK_B71C965C727ACA70 FOREIGN KEY (parent_id) REFERENCES file_category (id)');
        $this->addSql('ALTER TABLE file_category ADD CONSTRAINT FK_B71C965CB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE file_comment ADD CONSTRAINT FK_CF12EB5793CB796C FOREIGN KEY (file_id) REFERENCES shared_file (id)');
        $this->addSql('ALTER TABLE file_comment ADD CONSTRAINT FK_CF12EB57F675F31B FOREIGN KEY (author_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE shared_file ADD CONSTRAINT FK_36695D8812469DE2 FOREIGN KEY (category_id) REFERENCES file_category (id)');
        $this->addSql('ALTER TABLE shared_file ADD CONSTRAINT FK_36695D8832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE shared_file ADD CONSTRAINT FK_36695D88166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE shared_file ADD CONSTRAINT FK_36695D88A2B28FE8 FOREIGN KEY (uploaded_by_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE file_category DROP FOREIGN KEY FK_B71C965C32C8A3DE');
        $this->addSql('ALTER TABLE file_category DROP FOREIGN KEY FK_B71C965C166D1F9C');
        $this->addSql('ALTER TABLE file_category DROP FOREIGN KEY FK_B71C965C727ACA70');
        $this->addSql('ALTER TABLE file_category DROP FOREIGN KEY FK_B71C965CB03A8386');
        $this->addSql('ALTER TABLE file_comment DROP FOREIGN KEY FK_CF12EB5793CB796C');
        $this->addSql('ALTER TABLE file_comment DROP FOREIGN KEY FK_CF12EB57F675F31B');
        $this->addSql('ALTER TABLE shared_file DROP FOREIGN KEY FK_36695D8812469DE2');
        $this->addSql('ALTER TABLE shared_file DROP FOREIGN KEY FK_36695D8832C8A3DE');
        $this->addSql('ALTER TABLE shared_file DROP FOREIGN KEY FK_36695D88166D1F9C');
        $this->addSql('ALTER TABLE shared_file DROP FOREIGN KEY FK_36695D88A2B28FE8');
        $this->addSql('DROP TABLE file_category');
        $this->addSql('DROP TABLE file_comment');
        $this->addSql('DROP TABLE shared_file');
    }
}
