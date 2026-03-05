<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260304012952 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation ADD google_event_id VARCHAR(255) DEFAULT NULL, CHANGE categorie categorie VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT NULL, CHANGE google_id google_id VARCHAR(100) DEFAULT NULL, CHANGE linkedin_id linkedin_id VARCHAR(100) DEFAULT NULL, CHANGE roles roles JSON DEFAULT NULL, CHANGE reset_token reset_token VARCHAR(255) DEFAULT NULL, CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT NULL, CHANGE avatar_name avatar_name VARCHAR(255) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL, CHANGE face_embedding face_embedding JSON DEFAULT NULL');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE formation DROP google_event_id, CHANGE categorie categorie VARCHAR(100) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE password password VARCHAR(255) DEFAULT \'NULL\', CHANGE google_id google_id VARCHAR(100) DEFAULT \'NULL\', CHANGE linkedin_id linkedin_id VARCHAR(100) DEFAULT \'NULL\', CHANGE roles roles LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`, CHANGE reset_token reset_token VARCHAR(255) DEFAULT \'NULL\', CHANGE reset_token_expires_at reset_token_expires_at DATETIME DEFAULT \'NULL\', CHANGE avatar_name avatar_name VARCHAR(255) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\', CHANGE face_embedding face_embedding LONGTEXT DEFAULT NULL COLLATE `utf8mb4_bin`');
    }
}
