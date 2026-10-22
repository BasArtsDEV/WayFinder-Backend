<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261022071629 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP CONSTRAINT fk_dbdcd7e1b36117a6');
        $this->addSql('DROP INDEX idx_dbdcd7e1b36117a6');
        $this->addSql('ALTER TABLE enrollment RENAME COLUMN participant_team_id TO team_id');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1296CD8AE FOREIGN KEY (team_id) REFERENCES team (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1296CD8AE ON enrollment (team_id)');
        $this->addSql('ALTER TABLE team ADD role VARCHAR(11) DEFAULT NULL');
        $this->addSql('UPDATE team SET role = type');
        $this->addSql('ALTER TABLE team ALTER role SET NOT NULL');
        $this->addSql('ALTER TABLE team DROP type');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP CONSTRAINT FK_DBDCD7E1296CD8AE');
        $this->addSql('DROP INDEX IDX_DBDCD7E1296CD8AE');
        $this->addSql('ALTER TABLE enrollment RENAME COLUMN team_id TO participant_team_id');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT fk_dbdcd7e1b36117a6 FOREIGN KEY (participant_team_id) REFERENCES team (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_dbdcd7e1b36117a6 ON enrollment (participant_team_id)');
        $this->addSql('ALTER TABLE team ADD type VARCHAR(255) DEFAULT NULL');
        $this->addSql('UPDATE team SET type = role');
        $this->addSql('ALTER TABLE team ALTER type SET NOT NULL');
        $this->addSql('ALTER TABLE team DROP role');
    }
}
