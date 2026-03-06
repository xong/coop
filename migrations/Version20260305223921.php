<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305223921 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE calendar_event (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, location VARCHAR(255) DEFAULT NULL, start_at DATETIME NOT NULL, end_at DATETIME DEFAULT NULL, all_day TINYINT NOT NULL, created_at DATETIME NOT NULL, organization_id INT NOT NULL, project_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_57FA09C932C8A3DE (organization_id), INDEX IDX_57FA09C9166D1F9C (project_id), INDEX IDX_57FA09C9B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE event_attendees (calendar_event_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_4E5C55187495C8E3 (calendar_event_id), INDEX IDX_4E5C5518A76ED395 (user_id), PRIMARY KEY (calendar_event_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, status VARCHAR(20) NOT NULL, priority VARCHAR(20) NOT NULL, due_date DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, completed_at DATETIME DEFAULT NULL, organization_id INT NOT NULL, project_id INT DEFAULT NULL, created_by_id INT NOT NULL, INDEX IDX_527EDB2532C8A3DE (organization_id), INDEX IDX_527EDB25166D1F9C (project_id), INDEX IDX_527EDB25B03A8386 (created_by_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE task_assignees (task_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_6DEED38D8DB60186 (task_id), INDEX IDX_6DEED38DA76ED395 (user_id), PRIMARY KEY (task_id, user_id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE calendar_event ADD CONSTRAINT FK_57FA09C932C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE calendar_event ADD CONSTRAINT FK_57FA09C9166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE calendar_event ADD CONSTRAINT FK_57FA09C9B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE event_attendees ADD CONSTRAINT FK_4E5C55187495C8E3 FOREIGN KEY (calendar_event_id) REFERENCES calendar_event (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_attendees ADD CONSTRAINT FK_4E5C5518A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB2532C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25166D1F9C FOREIGN KEY (project_id) REFERENCES project (id)');
        $this->addSql('ALTER TABLE task ADD CONSTRAINT FK_527EDB25B03A8386 FOREIGN KEY (created_by_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE task_assignees ADD CONSTRAINT FK_6DEED38D8DB60186 FOREIGN KEY (task_id) REFERENCES task (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE task_assignees ADD CONSTRAINT FK_6DEED38DA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE calendar_event DROP FOREIGN KEY FK_57FA09C932C8A3DE');
        $this->addSql('ALTER TABLE calendar_event DROP FOREIGN KEY FK_57FA09C9166D1F9C');
        $this->addSql('ALTER TABLE calendar_event DROP FOREIGN KEY FK_57FA09C9B03A8386');
        $this->addSql('ALTER TABLE event_attendees DROP FOREIGN KEY FK_4E5C55187495C8E3');
        $this->addSql('ALTER TABLE event_attendees DROP FOREIGN KEY FK_4E5C5518A76ED395');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB2532C8A3DE');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25166D1F9C');
        $this->addSql('ALTER TABLE task DROP FOREIGN KEY FK_527EDB25B03A8386');
        $this->addSql('ALTER TABLE task_assignees DROP FOREIGN KEY FK_6DEED38D8DB60186');
        $this->addSql('ALTER TABLE task_assignees DROP FOREIGN KEY FK_6DEED38DA76ED395');
        $this->addSql('DROP TABLE calendar_event');
        $this->addSql('DROP TABLE event_attendees');
        $this->addSql('DROP TABLE task');
        $this->addSql('DROP TABLE task_assignees');
    }
}
