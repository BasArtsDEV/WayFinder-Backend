<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261023141842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment ADD started_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrollment ADD last_resumed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrollment ADD finished_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL');
        $this->addSql('ALTER TABLE enrollment ADD riddle_elapsed_time INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE submission ALTER submitted_value TYPE VARCHAR(2048)');
        $this->addSql('ALTER TABLE submission ALTER submission_date TYPE TIMESTAMP(0) WITHOUT TIME ZONE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP started_at');
        $this->addSql('ALTER TABLE enrollment DROP last_resumed_at');
        $this->addSql('ALTER TABLE enrollment DROP finished_at');
        $this->addSql('ALTER TABLE enrollment DROP riddle_elapsed_time');
        $this->addSql('ALTER TABLE submission ALTER submitted_value TYPE VARCHAR(255)');
        $this->addSql('ALTER TABLE submission ALTER submission_date TYPE DATE');
    }
}
