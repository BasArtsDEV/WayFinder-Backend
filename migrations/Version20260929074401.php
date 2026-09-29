<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260929074401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team ADD leader_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE team ADD CONSTRAINT FK_C4E0A61F73154ED4 FOREIGN KEY (leader_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_C4E0A61F73154ED4 ON team (leader_id)');
        $this->addSql('ALTER TABLE user_team DROP CONSTRAINT fk_be61ead6a76ed395');
        $this->addSql('ALTER TABLE user_team ADD CONSTRAINT FK_BE61EAD6A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE team DROP CONSTRAINT FK_C4E0A61F73154ED4');
        $this->addSql('DROP INDEX IDX_C4E0A61F73154ED4');
        $this->addSql('ALTER TABLE team DROP leader_id');
        $this->addSql('ALTER TABLE user_team DROP CONSTRAINT FK_BE61EAD6A76ED395');
        $this->addSql('ALTER TABLE user_team ADD CONSTRAINT fk_be61ead6a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
