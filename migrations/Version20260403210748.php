<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260403210748 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE simulation ADD status VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT fk_d3d3968ced697dd5');
        $this->addSql('DROP INDEX idx_d3d3968ced697dd5');
        $this->addSql('ALTER TABLE simulation_event RENAME COLUMN topology_id TO simulation_id');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT FK_D3D3968CFEC09103 FOREIGN KEY (simulation_id) REFERENCES simulation (id) NOT DEFERRABLE');
        $this->addSql('CREATE INDEX IDX_D3D3968CFEC09103 ON simulation_event (simulation_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE simulation DROP status');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT FK_D3D3968CFEC09103');
        $this->addSql('DROP INDEX IDX_D3D3968CFEC09103');
        $this->addSql('ALTER TABLE simulation_event RENAME COLUMN simulation_id TO topology_id');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT fk_d3d3968ced697dd5 FOREIGN KEY (topology_id) REFERENCES topology (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_d3d3968ced697dd5 ON simulation_event (topology_id)');
    }
}
