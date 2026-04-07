<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260407011639 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE simulation DROP CONSTRAINT fk_cbda467bed697dd5');
        $this->addSql('ALTER TABLE simulation ADD CONSTRAINT FK_CBDA467BED697DD5 FOREIGN KEY (topology_id) REFERENCES topology (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT fk_d3d3968cfd227bf');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT fk_d3d3968c8d6526bc');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT FK_D3D3968CFD227BF FOREIGN KEY (source_node_id) REFERENCES topology_node (id) ON DELETE CASCADE NOT DEFERRABLE');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT FK_D3D3968C8D6526BC FOREIGN KEY (target_node_id) REFERENCES topology_node (id) ON DELETE CASCADE NOT DEFERRABLE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE simulation DROP CONSTRAINT FK_CBDA467BED697DD5');
        $this->addSql('ALTER TABLE simulation ADD CONSTRAINT fk_cbda467bed697dd5 FOREIGN KEY (topology_id) REFERENCES topology (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT FK_D3D3968CFD227BF');
        $this->addSql('ALTER TABLE simulation_event DROP CONSTRAINT FK_D3D3968C8D6526BC');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT fk_d3d3968cfd227bf FOREIGN KEY (source_node_id) REFERENCES topology_node (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE simulation_event ADD CONSTRAINT fk_d3d3968c8d6526bc FOREIGN KEY (target_node_id) REFERENCES topology_node (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
