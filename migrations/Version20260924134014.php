<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924134014 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user_enrollement DROP CONSTRAINT fk_b21beb10a76ed395');
        $this->addSql('ALTER TABLE user_enrollement DROP CONSTRAINT fk_b21beb10260d5a60');
        $this->addSql('DROP TABLE user_enrollement');
        $this->addSql('ALTER TABLE enrollement ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE enrollement ADD CONSTRAINT FK_1B002285A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1B002285A76ED395 ON enrollement (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_enrollement (user_id INT NOT NULL, enrollement_id INT NOT NULL, PRIMARY KEY (user_id, enrollement_id))');
        $this->addSql('CREATE INDEX idx_b21beb10260d5a60 ON user_enrollement (enrollement_id)');
        $this->addSql('CREATE INDEX idx_b21beb10a76ed395 ON user_enrollement (user_id)');
        $this->addSql('ALTER TABLE user_enrollement ADD CONSTRAINT fk_b21beb10a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_enrollement ADD CONSTRAINT fk_b21beb10260d5a60 FOREIGN KEY (enrollement_id) REFERENCES enrollement (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrollement DROP CONSTRAINT FK_1B002285A76ED395');
        $this->addSql('DROP INDEX IDX_1B002285A76ED395');
        $this->addSql('ALTER TABLE enrollement DROP user_id');
    }
}
