<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260305120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Formation capacity; Inscription statut absent + absent_follow_up_sent_at.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation ADD capacity INT DEFAULT NULL');
        $this->addSql('ALTER TABLE inscription ADD absent_follow_up_sent_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE formation DROP capacity');
        $this->addSql('ALTER TABLE inscription DROP absent_follow_up_sent_at');
    }
}
