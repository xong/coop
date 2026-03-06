<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260305232925 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE app_notifications (id INT AUTO_INCREMENT NOT NULL, event_type VARCHAR(50) NOT NULL, title VARCHAR(255) NOT NULL, body VARCHAR(500) NOT NULL, url VARCHAR(500) DEFAULT NULL, is_read TINYINT NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_FA7D8D7FA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE dashboard_widgets (id INT AUTO_INCREMENT NOT NULL, widget_type VARCHAR(50) NOT NULL, position INT NOT NULL, is_enabled TINYINT NOT NULL, user_id INT NOT NULL, INDEX IDX_2CBC36ECA76ED395 (user_id), UNIQUE INDEX UNIQ_2CBC36ECA76ED39514295C96 (user_id, widget_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notification_settings (id INT AUTO_INCREMENT NOT NULL, event_type VARCHAR(50) NOT NULL, in_app TINYINT NOT NULL, email TINYINT NOT NULL, email_frequency VARCHAR(20) NOT NULL, user_id INT NOT NULL, INDEX IDX_B0559860A76ED395 (user_id), UNIQUE INDEX UNIQ_B0559860A76ED39593151B82 (user_id, event_type), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE app_notifications ADD CONSTRAINT FK_FA7D8D7FA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE dashboard_widgets ADD CONSTRAINT FK_2CBC36ECA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE notification_settings ADD CONSTRAINT FK_B0559860A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE app_notifications DROP FOREIGN KEY FK_FA7D8D7FA76ED395');
        $this->addSql('ALTER TABLE dashboard_widgets DROP FOREIGN KEY FK_2CBC36ECA76ED395');
        $this->addSql('ALTER TABLE notification_settings DROP FOREIGN KEY FK_B0559860A76ED395');
        $this->addSql('DROP TABLE app_notifications');
        $this->addSql('DROP TABLE dashboard_widgets');
        $this->addSql('DROP TABLE notification_settings');
    }
}
