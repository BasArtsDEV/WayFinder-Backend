<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261020134122 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment ADD participant_team_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_DBDCD7E1B36117A6 FOREIGN KEY (participant_team_id) REFERENCES team (id) NOT DEFERRABLE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_ENROLLMENT_USER_TREASURE_HUNT ON enrollment (user_id, treasure_hunt_id)');
        $this->addSql('CREATE INDEX IDX_DBDCD7E1B36117A6 ON enrollment (participant_team_id)');
        $this->addSql('ALTER TABLE riddle ADD score INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE team ADD type VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE team ALTER leader_id SET NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE enrollment DROP CONSTRAINT FK_DBDCD7E1B36117A6');
        $this->addSql('DROP INDEX UNIQ_ENROLLMENT_USER_TREASURE_HUNT');
        $this->addSql('DROP INDEX IDX_DBDCD7E1B36117A6');
        $this->addSql('ALTER TABLE enrollment DROP participant_team_id');
        $this->addSql('ALTER TABLE riddle DROP score');
        $this->addSql('ALTER TABLE team DROP type');
        $this->addSql('ALTER TABLE team ALTER leader_id DROP NOT NULL');
    }
}
