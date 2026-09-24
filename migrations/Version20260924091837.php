<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260924091837 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location DROP latitude');
        $this->addSql('ALTER TABLE location DROP longitude');
        $this->addSql('ALTER TABLE riddle ALTER location_id DROP NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE location ADD latitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE location ADD longitude DOUBLE PRECISION DEFAULT NULL');
        $this->addSql('ALTER TABLE riddle ALTER location_id SET NOT NULL');
    }
}
