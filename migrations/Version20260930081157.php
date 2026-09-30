<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260930081157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_1b0022859e2b9ccd RENAME TO IDX_DBDCD7E19E2B9CCD');
        $this->addSql('ALTER INDEX idx_1b002285d25ee088 RENAME TO IDX_DBDCD7E1D25EE088');
        $this->addSql('ALTER INDEX idx_1b002285a76ed395 RENAME TO IDX_DBDCD7E1A76ED395');
        $this->addSql('ALTER INDEX idx_db055af3260d5a60 RENAME TO IDX_DB055AF38F7DB25B');
        $this->addSql('ALTER TABLE team ADD banner BYTEA DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER INDEX idx_dbdcd7e1d25ee088 RENAME TO idx_1b002285d25ee088');
        $this->addSql('ALTER INDEX idx_dbdcd7e19e2b9ccd RENAME TO idx_1b0022859e2b9ccd');
        $this->addSql('ALTER INDEX idx_dbdcd7e1a76ed395 RENAME TO idx_1b002285a76ed395');
        $this->addSql('ALTER INDEX idx_db055af38f7db25b RENAME TO idx_db055af3260d5a60');
        $this->addSql('ALTER TABLE team DROP banner');
    }
}
