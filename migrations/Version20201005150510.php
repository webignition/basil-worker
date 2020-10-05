<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20201005150510 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add Test::manifest';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE test ADD manifest_path VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE test DROP manifest_path');
    }
}
