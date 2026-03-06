<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305221520 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, owner_id INT NOT NULL, UNIQUE INDEX UNIQ_C1EE637C989D9B62 (slug), INDEX IDX_C1EE637C7E3C61F9 (owner_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization_invitation (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, token VARCHAR(100) NOT NULL, role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, accepted_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, invited_by_id INT NOT NULL, UNIQUE INDEX UNIQ_1846F34D5F37A13B (token), INDEX IDX_1846F34D32C8A3DE (organization_id), INDEX IDX_1846F34DA7B4A7E3 (invited_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE organization_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL, organization_id INT NOT NULL, user_id INT NOT NULL, invited_by_id INT DEFAULT NULL, INDEX IDX_756A2A8D32C8A3DE (organization_id), INDEX IDX_756A2A8DA76ED395 (user_id), INDEX IDX_756A2A8DA7B4A7E3 (invited_by_id), UNIQUE INDEX UNIQ_756A2A8D32C8A3DEA76ED395 (organization_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, slug VARCHAR(150) NOT NULL, description LONGTEXT DEFAULT NULL, is_public TINYINT NOT NULL, created_at DATETIME NOT NULL, organization_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_2FB3D0EE32C8A3DE (organization_id), INDEX IDX_2FB3D0EEB03A8386 (created_by_id), UNIQUE INDEX UNIQ_2FB3D0EE32C8A3DE989D9B62 (organization_id, slug), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_invitation (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, token VARCHAR(100) NOT NULL, role VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, expires_at DATETIME NOT NULL, accepted_at DATETIME DEFAULT NULL, project_id INT NOT NULL, invited_by_id INT NOT NULL, UNIQUE INDEX UNIQ_E9BB1A905F37A13B (token), INDEX IDX_E9BB1A90166D1F9C (project_id), INDEX IDX_E9BB1A90A7B4A7E3 (invited_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE project_member (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(20) NOT NULL, joined_at DATETIME NOT NULL, project_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_67401132166D1F9C (project_id), INDEX IDX_67401132A76ED395 (user_id), UNIQUE INDEX UNIQ_67401132166D1F9CA76ED395 (project_id, user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) NOT NULL, avatar VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, is_verified TINYINT NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE organization ADD CONSTRAINT FK_C1EE637C7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE organization_invitation ADD CONSTRAINT FK_1846F34D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_invitation ADD CONSTRAINT FK_1846F34DA7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8D32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE organization_member ADD CONSTRAINT FK_756A2A8DA7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EE32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE project ADD CONSTRAINT FK_2FB3D0EEB03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_invitation ADD CONSTRAINT FK_E9BB1A90166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_invitation ADD CONSTRAINT FK_E9BB1A90A7B4A7E3 FOREIGN KEY (invited_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE project_member ADD CONSTRAINT FK_67401132A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE organization DROP FOREIGN KEY FK_C1EE637C7E3C61F9');
        $this->addSql('ALTER TABLE organization_invitation DROP FOREIGN KEY FK_1846F34D32C8A3DE');
        $this->addSql('ALTER TABLE organization_invitation DROP FOREIGN KEY FK_1846F34DA7B4A7E3');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8D32C8A3DE');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA76ED395');
        $this->addSql('ALTER TABLE organization_member DROP FOREIGN KEY FK_756A2A8DA7B4A7E3');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EE32C8A3DE');
        $this->addSql('ALTER TABLE project DROP FOREIGN KEY FK_2FB3D0EEB03A8386');
        $this->addSql('ALTER TABLE project_invitation DROP FOREIGN KEY FK_E9BB1A90166D1F9C');
        $this->addSql('ALTER TABLE project_invitation DROP FOREIGN KEY FK_E9BB1A90A7B4A7E3');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132166D1F9C');
        $this->addSql('ALTER TABLE project_member DROP FOREIGN KEY FK_67401132A76ED395');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP TABLE organization_invitation');
        $this->addSql('DROP TABLE organization_member');
        $this->addSql('DROP TABLE project');
        $this->addSql('DROP TABLE project_invitation');
        $this->addSql('DROP TABLE project_member');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
