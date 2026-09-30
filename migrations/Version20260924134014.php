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
        $this->addSql('ALTER TABLE user_enrollment DROP CONSTRAINT fk_b21beb10a76ed395');
        $this->addSql('ALTER TABLE user_enrollment DROP CONSTRAINT fk_b21beb10260d5a60');
        $this->addSql('DROP TABLE user_enrollment');
        $this->addSql('ALTER TABLE enrollment ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE enrollment ADD CONSTRAINT FK_1B002285A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_1B002285A76ED395 ON enrollment (user_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE user_enrollment (user_id INT NOT NULL, enrollment_id INT NOT NULL, PRIMARY KEY (user_id, enrollment_id))');
        $this->addSql('CREATE INDEX idx_b21beb10260d5a60 ON user_enrollment (enrollment_id)');
        $this->addSql('CREATE INDEX idx_b21beb10a76ed395 ON user_enrollment (user_id)');
        $this->addSql('ALTER TABLE user_enrollment ADD CONSTRAINT fk_b21beb10a76ed395 FOREIGN KEY (user_id) REFERENCES "user" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE user_enrollment ADD CONSTRAINT fk_b21beb10260d5a60 FOREIGN KEY (enrollment_id) REFERENCES enrollment (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE enrollment DROP CONSTRAINT FK_1B002285A76ED395');
        $this->addSql('DROP INDEX IDX_1B002285A76ED395');
        $this->addSql('ALTER TABLE enrollment DROP user_id');
    }
}
